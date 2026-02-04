<?php

namespace App\Command;
use App\Entity\GwpTrx;
use App\Entity\Product;
use App\Entity\Submission;
use App\Entity\ProcessingState;
use App\AppBundle\Util\EnumError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:process-gwp-trx')]
class ProcessGwpTrx extends Command
{

    private $passwordEncoder;
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $encoder)
    {
        $this->passwordEncoder = $encoder;
        $this->manager = $entityManager;
        parent::__construct();
    }

    /**
     * Add calendar days (including weekends) from a given date
     * 
     * @param \DateTime $startDate
     * @param int $days
     * @return \DateTime
     */
    private function addCalendarDays(\DateTime $startDate, int $days): \DateTime
    {
        $date = clone $startDate;
        $date->add(new \DateInterval("P{$days}D"));
        
        return $date;
    }

    protected function configure(): void
    {
        // $this->addArgument('parameter', InputArgument::OPTIONAL, 'Parameter required');        
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            // Start a transaction
            $this->manager->getConnection()->beginTransaction();
            
            // Get all unprocessed GwpTrx records in FIFO order (by ID)
            $allGwpTrx = $this->manager->getRepository(GwpTrx::class)->findBy(['is_completed' => 0], ['id' => 'ASC']);
            $totalCount = count($allGwpTrx);
            
            $io->writeln("Found {$totalCount} GwpTrx records to process");
            
            // Log the first few GwpTrx IDs for debugging
            if ($totalCount > 0) {
                $firstFew = array_slice($allGwpTrx, 0, min(10, $totalCount));
                $ids = array_map(function($gwp) { return $gwp->getId(); }, $firstFew);
                $io->writeln("🔍 First GwpTrx IDs to process: " . implode(', ', $ids));
            }
            
            if ($totalCount == 0) {
                $io->success('No records to process.');
                return Command::SUCCESS;
            }
            
            // Step 1: Filter submissions based on limits
            $validSubmissions = [];
            $processedCount = 0;
            
            $io->writeln("🔍 Step 1: Filtering submissions based on limits...");
            $progressBar = $io->createProgressBar($totalCount);
            
            foreach ($allGwpTrx as $gwpTrx) {
                $submission = $this->manager->getRepository(Submission::class)->findOneBy(['id' => $gwpTrx->getSubId()]);
                
                if (!$submission) {
                    $gwpTrx->setCompleted(true);
                    $gwpTrx->setCompletedDate(new \DateTime());
                    $this->manager->persist($gwpTrx);
                    $progressBar->advance();
                    continue;
                }
                
                // Update field7 from GwpTrx
                $submission->setField7($gwpTrx->getField7() ?: '');
                $this->manager->persist($submission);
                
                // Check if submission passes all limit filters
                if ($this->passesLimitFilters($submission, $io)) {
                    $validSubmissions[] = $gwpTrx;
                } else {
                    // Mark as completed if it doesn't pass filters
                    $gwpTrx->setCompleted(true);
                    $gwpTrx->setCompletedDate(new \DateTime());
                    $this->manager->persist($gwpTrx);
                }
                
                $processedCount++;
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $io->newLine(2);
            
            // Flush the changes from Step 1 (marking failed records as completed)
            $this->manager->flush();
            
            $validCount = count($validSubmissions);
            $io->writeln("✅ Step 1 Complete: {$validCount} submissions passed all filters");
            
            // Re-fetch only unprocessed GwpTrx records for batch processing
            $unprocessedGwpTrx = $this->manager->getRepository(GwpTrx::class)->findBy(['is_completed' => 0], ['id' => 'ASC']);
            $unprocessedCount = count($unprocessedGwpTrx);
            
            $io->writeln("🔄 Re-fetched {$unprocessedCount} unprocessed GwpTrx records for batch processing");
            
            // Verify consistency
            if ($unprocessedCount !== $validCount) {
                $io->writeln("⚠️  Warning: Mismatch between valid submissions ({$validCount}) and unprocessed records ({$unprocessedCount})");
            }
            
            // Step 2: Process in batches of 50 with category cycling
            if ($unprocessedCount > 0) {
                $this->processBatches($unprocessedGwpTrx, $io);
            }
            
            // Commit the transaction
            $this->manager->getConnection()->commit();
            $io->success("GWP Trx Command Completed. Processed {$processedCount} records total, {$validCount} valid for redemption.");

        } catch (\Exception $e) {
            // Only roll back if a transaction is active
            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }
            $output->writeln([
                '',
                'Error: ' . $e->getMessage(),
                'File: ' . $e->getFile() . ':' . $e->getLine()
            ]);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Check if submission passes all limit filters
     */
    private function passesLimitFilters(Submission $submission, SymfonyStyle $io, bool $verbose = true): bool
    {
        $nationalId = $submission->getNationalId();
        $mobileNo = $submission->getMobileNo();
        
        if (!$nationalId || !$mobileNo) {
            if ($verbose) {
                $io->writeln("  ⚠️  Submission ID: {$submission->getId()} missing national_id or mobile_no - skipping");
            }
            return false;
        }
        
        // Get all APPROVED submissions with products (excluding CVSTOFT and IN-STORE)
        $allApprovedSubmissions = $this->manager->getRepository(Submission::class)->findBy(['status' => 'APPROVED']);
        $submissionsWithProducts = array_filter($allApprovedSubmissions, function($sub) {
            return $sub->getProductRef() != null && 
                   $sub->getSubmitCode() !== 'CVSTOFT' && 
                   $sub->getSubmitCode() !== 'IN-STORE';
        });
        
        // Filter 1: Check national_id limit (max 4 products)
        $nationalIdSubmissions = array_filter($submissionsWithProducts, function($sub) use ($nationalId) {
            return $sub->getNationalId() === $nationalId;
        });
        
        $nationalIdProductCount = 0;
        foreach ($nationalIdSubmissions as $sub) {
            $nationalIdProductCount += $sub->getProductRefCount();
        }
        
        if ($nationalIdProductCount >= 4) {
            if ($verbose) {
                $io->writeln("   Submission ID: {$submission->getId()} - National ID {$nationalId} has reached limit ({$nationalIdProductCount} products)");
            }
            $submission->setField1('LIMIT REACHED - NATIONAL ID');
            $this->manager->persist($submission);
            return false;
        }
        
        // Filter 2: Check mobile_no limit (max 4 products)
        $mobileNoSubmissions = array_filter($submissionsWithProducts, function($sub) use ($mobileNo) {
            return $sub->getMobileNo() === $mobileNo;
        });
        
        $mobileNoProductCount = 0;
        foreach ($mobileNoSubmissions as $sub) {
            $mobileNoProductCount += $sub->getProductRefCount();
        }
        
        if ($mobileNoProductCount >= 4) {
            if ($verbose) {
                $io->writeln("  🚫 Submission ID: {$submission->getId()} - Mobile No {$mobileNo} has reached limit ({$mobileNoProductCount} products)");
            }
            $submission->setField1('LIMIT REACHED - MOBILE NO');
            $this->manager->persist($submission);
            return false;
        }
        
        // Filter 3: Check combined national_id + mobile_no limit (max 4 products total)
        $combinedSubmissions = array_filter($submissionsWithProducts, function($sub) use ($nationalId, $mobileNo) {
            return $sub->getNationalId() === $nationalId || $sub->getMobileNo() === $mobileNo;
        });
        
        $combinedProductCount = 0;
        foreach ($combinedSubmissions as $sub) {
            $combinedProductCount += $sub->getProductRefCount();
        }
        
        if ($combinedProductCount >= 4) {
            if ($verbose) {
                $io->writeln("  🚫 Submission ID: {$submission->getId()} - Combined National ID + Mobile No has reached limit ({$combinedProductCount} products)");
            }
            $submission->setField1('LIMIT REACHED - COMBINED');
            $this->manager->persist($submission);
            return false;
        }
        
        if ($verbose) {
            $io->writeln("  ✅ Submission ID: {$submission->getId()} passed all filters (National ID: {$nationalIdProductCount}, Mobile: {$mobileNoProductCount}, Combined: {$combinedProductCount})");
        }
        return true;
    }
    
    /**
     * Process valid submissions in batches of 50 with category cycling
     */
    private function processBatches(array $validGwpTrx, SymfonyStyle $io): void
    {
        $batchSize = 50;
        $categories = ['SPICY_YUMSTER', 'PITCH_PURRFECT', 'DJ_FYRE', 'BLAZING_BITES'];
        
        // Get last processed category
        $processingStateRepo = $this->manager->getRepository(ProcessingState::class);
        $lastCategory = $processingStateRepo->getLastProcessedCategory('gwp_trx_processing');
        
        // Determine next category
        if ($lastCategory) {
            $lastIndex = array_search($lastCategory, $categories);
            $currentCategoryIndex = ($lastIndex + 1) % count($categories);
        } else {
            $currentCategoryIndex = 0; // Start with 'SPICY_YUMSTER'
        }
        
        $availableSubmissions = count($validGwpTrx);
        
        $io->writeln("🔄 Step 2: Processing batches of {$batchSize} products each");
        
        // Log the valid GwpTrx IDs in order
        $validIds = array_map(function($gwp) { return $gwp->getId(); }, $validGwpTrx);
        $io->writeln("📋 Valid GwpTrx IDs in FIFO order: " . implode(', ', array_slice($validIds, 0, 20)) . (count($validIds) > 20 ? '...' : ''));
        
        if ($availableSubmissions == 0) {
            $io->writeln("⚠️  No valid submissions available for processing.");
            return;
        }
        
        // Guaranteed complete batch processing: Only process batches that can be filled to exactly 50
        $totalPossibleBatches = intval($availableSubmissions / $batchSize);
        
        // Very aggressive approach: Based on actual performance showing 83% success rate
        // Use 95% success rate for maximum processing with minimal safety buffer
        $estimatedSuccessRate = 0.95;
        $estimatedSuccessfulSubmissions = intval($availableSubmissions * $estimatedSuccessRate);
        $guaranteedCompleteBatches = intval($estimatedSuccessfulSubmissions / $batchSize);
        
        // No buffer: process all guaranteed complete batches
        $batchesToProcess = $guaranteedCompleteBatches;
        $submissionsToProcess = $availableSubmissions; // Use all available, but stop when batch is complete
        
        $io->writeln("📊 Available submissions: {$availableSubmissions}");
        $io->writeln("📊 Estimated successful assignments (95%): {$estimatedSuccessfulSubmissions}");
        $io->writeln("📊 Guaranteed complete batches: {$guaranteedCompleteBatches}");
        $io->writeln("📊 Batches to process (no buffer): {$batchesToProcess}");
        $io->writeln("📊 Target products: " . ($batchesToProcess * $batchSize));
        
        // Minimum requirement: need at least 1 guaranteed complete batch
        if ($guaranteedCompleteBatches < 1) {
            $minimumSubmissions = intval($batchSize / $estimatedSuccessRate); // Need enough for 1 batch
            $io->writeln("⚠️  Cannot guarantee any complete batches of {$batchSize} products.");
            $io->writeln("⚠️  Need at least ~{$minimumSubmissions} submissions to guarantee 1 complete batch.");
            $io->writeln("⚠️  Current: {$availableSubmissions} submissions available. Processing skipped.");
            return;
        }
        
        if ($batchesToProcess == 0) {
            $io->writeln("⚠️  No batches to process.");
            return;
        }
        
        $io->writeln("✅ Dynamic processing approved:");
        
        $io->writeln("🎯 Last processed category: {$lastCategory}");
        $io->writeln("🎯 Starting with category: {$categories[$currentCategoryIndex]}");
        
        $processedBatches = 0;
        $totalProductsAssigned = 0; // Track actual products assigned across all batches
        
        $globalIndex = 0; // Track position in the validGwpTrx array
        
        $batchNum = 0;
        while ($globalIndex < $submissionsToProcess && $batchNum < $batchesToProcess) {
            $batchNum++;
            $currentCategory = $categories[$currentCategoryIndex];
            
            $io->writeln("📦 Processing Batch {$batchNum}/{$batchesToProcess} - Category: {$currentCategory}");
            
            $batchProcessed = 0;
            $batchAttempted = 0;
            $processedGwpTrx = [];
            $batchAssignedProducts = []; // Track products assigned in this specific batch
            
            // Continue processing until we get exactly 50 successful assignments
            // Keep going through all available submissions if needed
            while ($batchProcessed < $batchSize && $globalIndex < count($validGwpTrx)) {
                $gwpTrx = $validGwpTrx[$globalIndex];
                
                $submission = $this->manager->getRepository(Submission::class)->findOneBy(['id' => $gwpTrx->getSubId()]);
                
                $io->writeln("   🔄 Processing GwpTrx ID: {$gwpTrx->getId()}, Sub ID: {$gwpTrx->getSubId()}, Category: {$currentCategory} (Attempt " . ($batchAttempted + 1) . ")");
                
                $assignedProduct = $this->assignProductToSubmission($submission, $currentCategory, $io);
                if ($assignedProduct) {
                    $batchProcessed++;
                    $batchAssignedProducts[] = $assignedProduct; // Track this product for potential rollback
                    $io->writeln("   ✅ Product assigned successfully (#{$batchProcessed}/{$batchSize})");
                } else {
                    $io->writeln("   ❌ Product assignment failed - continuing to next submission");
                }
                
                // Mark GwpTrx as completed regardless of assignment success
                $gwpTrx->setCompleted(true);
                $gwpTrx->setCompletedDate(new \DateTime());
                $this->manager->persist($gwpTrx);
                $processedGwpTrx[] = $gwpTrx;
                
                $globalIndex++;
                $batchAttempted++;
            }
            
            // Log batch summary
            $processedIds = array_map(function($gwp) { return $gwp->getId(); }, $processedGwpTrx);
            $io->writeln("   📋 Processed GwpTrx IDs: " . implode(', ', array_slice($processedIds, 0, 10)) . (count($processedIds) > 10 ? '...' : ''));
            
            // Flush batch (for GwpTrx completion updates - product assignments are flushed individually)
            $this->manager->flush();
            $io->writeln("✅ Batch {$batchNum} completed: {$batchProcessed}/{$batchSize} products assigned from {$batchAttempted} attempts");
            
            // Add to total products assigned
            $totalProductsAssigned += $batchProcessed;
            
            // Update last processed category
            $processingStateRepo->updateLastProcessedCategory('gwp_trx_processing', $currentCategory);
            
            // Move to next category
            $currentCategoryIndex = ($currentCategoryIndex + 1) % count($categories);
            $processedBatches++;
            
            // Only accept batches with exactly 50 products
            if ($batchProcessed == $batchSize) {
                $io->writeln("✅ Batch {$batchNum} completed successfully with exactly {$batchSize} products");
            } else {
                // If we couldn't get exactly 50 successful assignments, stop processing
                $io->writeln("⚠️  Batch {$batchNum} only assigned {$batchProcessed} products (required: {$batchSize}).");
                $io->writeln("🛑 Stopping batch processing - cannot guarantee complete batches.");
                
                // Rollback this incomplete batch by marking the GwpTrx as not completed
                foreach ($processedGwpTrx as $gwp) {
                    $gwp->setCompleted(false);
                    $gwp->setCompletedDate(null);
                    $this->manager->persist($gwp);
                }
                
                // CRITICAL FIX: Unlock products that were locked during this failed batch
                $this->unlockProductsFromFailedBatch($currentCategory, $batchAssignedProducts, $io);
                
                // Flush the rollback changes to database
                $this->manager->flush();
                
                // Adjust totals to exclude this incomplete batch
                $totalProductsAssigned -= $batchProcessed;
                $processedBatches--; // Don't count this incomplete batch
                
                $io->writeln("🔄 Rolled back incomplete batch {$batchNum} and unlocked products");
                break; // Stop processing further batches
            }
        }
        
        $expectedProducts = $processedBatches * $batchSize;
        $remainingSubmissions = count($validGwpTrx) - $globalIndex;
        $bufferSubmissions = count($validGwpTrx) - $submissionsToProcess;
        
        $io->writeln("🎉 Batch processing completed: {$processedBatches}/{$batchesToProcess} batches processed");
        $io->writeln("📊 Products assigned: {$totalProductsAssigned} (Target: {$expectedProducts})");
        $io->writeln("📊 Remaining valid submissions: {$remainingSubmissions}");
        $io->writeln("🛡️  Buffer submissions reserved: {$bufferSubmissions} (1 batch buffer)");
        
        if ($totalProductsAssigned < $expectedProducts) {
            $shortfall = $expectedProducts - $totalProductsAssigned;
            $io->writeln("⚠️  Shortfall: {$shortfall} products not assigned");
        } else if ($totalProductsAssigned == $expectedProducts) {
            $io->writeln("🎯 Perfect! Achieved exact target of {$expectedProducts} products");
        }
    }
    
    /**
     * Unlock products that were locked during a failed batch
     */
    private function unlockProductsFromFailedBatch(string $category, array $batchAssignedProducts, SymfonyStyle $io): void
    {
        // Use the specific products that were assigned in this batch
        $lockedProducts = $batchAssignedProducts;
        
        $unlockedCount = 0;
        $submissionsToUpdate = [];
        
        foreach ($lockedProducts as $product) {
    $submission = $product->getSubId();
            $productId = $product->getId();
            
            // Unlock the product completely
                    $product->setLocked(false);
            $product->setLockedDate(null);
            $product->setUser(null);
            $product->setSubId(null);
            $product->setDeliveryStatus(null);
            $product->setCourierStatus(null);
            $product->setDueDate(null);
            $product->setReceiverFullName(null);
            $product->setReceiverMobileNo(null);
            $product->setAddress1(null);
            $product->setAddress2(null);
            $product->setCity(null);
            $product->setState(null);
            $product->setPostcode(null);
            
            $this->manager->persist($product);
            $unlockedCount++;
            
            // Track submissions that need product_ref updates
            if ($submission) {
                $submissionsToUpdate[$submission->getId()] = $submission;
            }
            
            $io->writeln("    🔓 Unlocked Product ID: {$productId} (Category: {$category})");
        }
        
        // Update submission product_ref to remove unlocked products
        foreach ($submissionsToUpdate as $submission) {
            if ($submission->getProductRef()) {
                $productIds = explode(',', $submission->getProductRef());
                $productIds = array_map('trim', $productIds);
                
                // Remove products from this category that were just unlocked
                $remainingProductIds = [];
                foreach ($productIds as $pid) {
                    $product = $this->manager->getRepository(Product::class)->find($pid);
                    if ($product && ($product->getProductCategory() !== $category || $product->isLocked())) {
                        $remainingProductIds[] = $pid;
                    }
                }
                
                // Update or clear the product_ref
                if (count($remainingProductIds) > 0) {
                    $submission->setProductRef(implode(',', $remainingProductIds));
                } else {
                    $submission->setProductRef(null);
                }
                
                $this->manager->persist($submission);
                $io->writeln("    🧹 Updated product_ref for Submission ID: {$submission->getId()}");
            }
        }
        
        if ($unlockedCount > 0) {
            $io->writeln("🔓 Unlocked {$unlockedCount} products from failed batch in category {$category}");
        } else {
            $io->writeln("ℹ️  No products to unlock in category {$category}");
        }
    }

    /**
     * Assign a product to a submission
     */
    private function assignProductToSubmission(Submission $submission, string $category, SymfonyStyle $io): ?Product
    {
        // CRITICAL: Re-check limits right before assignment to prevent race conditions
        if (!$this->passesLimitFilters($submission, $io, false)) {
            $io->writeln("    🚫 Submission ID: {$submission->getId()} failed limit check during assignment - skipping");
            return null;
        }
        
        // Find and lock a product in the specified category
        $foundProductId = $this->manager->getRepository(Product::class)->findAndLock($submission->getUser(), $category);
        
        if (!$foundProductId) {
            $io->writeln("    ⚠️  No available products in category {$category} for Submission ID: {$submission->getId()}");
            return null;
        }
        
        $productEntity = $this->manager->getRepository(Product::class)->find($foundProductId);
        $io->writeln("    🎁 Found Product ID: {$foundProductId}, Code: {$productEntity->getProductCode()}, Category: {$productEntity->getProductCategory()}");
        
        // Set due date to 5 calendar days from locked_date
        $lockedDate = $productEntity->getLockedDate();
        $dueDate = $this->addCalendarDays($lockedDate, 5);
        $productEntity->setDueDate($dueDate);
        
        // Set product details
        $productEntity->setDeliveryStatus('PROCESSING');
        $productEntity->setCourierStatus('PROCESSING');
        $productEntity->setReceiverFullname($submission->getReceiverFullname());
        $productEntity->setReceiverMobileNo($submission->getReceiverMobileNo());
        $productEntity->setAddress1($submission->getAddress1());
        $productEntity->setAddress2($submission->getAddress2());
        $productEntity->setCity($submission->getCity());
        $productEntity->setPostcode($submission->getPostcode());
        $productEntity->setState($submission->getState());
        $productEntity->setDetailsUpdatedDate(new \DateTime());
        $productEntity->setUpdatedDate(new \DateTime());
        $productEntity->setContacted(false);
        $productEntity->setDeleted(false);
        
        // Set submission reference in product
        $productEntity->setSubId($submission);
        
        $this->manager->persist($productEntity);
        
        // Add product reference to submission
        $submission->addProductRef($productEntity->getId());
        $this->manager->persist($submission);
        
        // CRITICAL: Flush immediately to update database and prevent race conditions
        $this->manager->flush();
        
        $io->writeln("    🎁 Assigned Product ID: {$productEntity->getId()} (Category: {$category}) to Submission ID: {$submission->getId()}");
        
        return $productEntity;
    }
}