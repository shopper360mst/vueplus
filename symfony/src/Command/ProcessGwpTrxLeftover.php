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

#[AsCommand(name: 'app:process-gwp-trx-leftover')]
class ProcessGwpTrxLeftover extends Command
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
        $this->setDescription('Process leftover GwpTrx records that didnt complete in main batch processing');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            // Start a transaction
            $this->manager->getConnection()->beginTransaction();
            
            // Get all unprocessed GwpTrx records (leftovers) in FIFO order (by ID)
            $allGwpTrx = $this->manager->getRepository(GwpTrx::class)->findBy(['is_completed' => 0], ['id' => 'ASC']);
            $totalCount = count($allGwpTrx);
            
            $io->writeln("Found {$totalCount} leftover GwpTrx records to process");
            
            // Log the first few GwpTrx IDs for debugging
            if ($totalCount > 0) {
                $firstFew = array_slice($allGwpTrx, 0, min(10, $totalCount));
                $ids = array_map(function($gwp) { return $gwp->getId(); }, $firstFew);
                $io->writeln("🔍 First leftover GwpTrx IDs to process: " . implode(', ', $ids));
            }
            
            if ($totalCount == 0) {
                $io->success('No leftover records to process.');
                return Command::SUCCESS;
            }
            
            // Step 1: Filter submissions based on limits
            $validSubmissions = [];
            $processedCount = 0;
            
            $io->writeln("🔍 Step 1: Filtering leftover submissions based on limits...");
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
            
            // Flush the changes from Step 1
            $this->manager->flush();
            
            $validCount = count($validSubmissions);
            $io->writeln("✅ Step 1 Complete: {$validCount} leftover submissions passed all filters");
            
            // Re-fetch only unprocessed GwpTrx records for batch processing
            $unprocessedGwpTrx = $this->manager->getRepository(GwpTrx::class)->findBy(['is_completed' => 0], ['id' => 'ASC']);
            $unprocessedCount = count($unprocessedGwpTrx);
            
            $io->writeln("🔄 Re-fetched {$unprocessedCount} unprocessed leftover GwpTrx records for batch processing");
            
            // Verify consistency
            if ($unprocessedCount !== $validCount) {
                $io->writeln("⚠️  Warning: Mismatch between valid leftover submissions ({$validCount}) and unprocessed records ({$unprocessedCount})");
            }
            
            // Step 2: Process leftovers with flexible batching
            if ($unprocessedCount > 0) {
                $this->processLeftoverBatches($unprocessedGwpTrx, $io);
            }
            
            // Commit the transaction
            $this->manager->getConnection()->commit();
            $io->success("GWP Trx Leftover Command Completed. Processed {$processedCount} records total, {$validCount} valid for redemption.");

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
    private function passesLimitFilters(Submission $submission, SymfonyStyle $io, bool $verbose = true, bool $setLimitReached = true): bool
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
            if ($setLimitReached && !$submission->getProductRef()) {
                $submission->setField1('LIMIT REACHED - NATIONAL ID');
                $this->manager->persist($submission);
            }
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
            if ($setLimitReached && !$submission->getProductRef()) {
                $submission->setField1('LIMIT REACHED - MOBILE NO');
                $this->manager->persist($submission);
            }
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
            if ($setLimitReached && !$submission->getProductRef()) {
                $submission->setField1('LIMIT REACHED - COMBINED');
                $this->manager->persist($submission);
            }
            return false;
        }
        
        if ($verbose) {
            $io->writeln("  ✅ Submission ID: {$submission->getId()} passed all filters (National ID: {$nationalIdProductCount}, Mobile: {$mobileNoProductCount}, Combined: {$combinedProductCount})");
        }
        return true;
    }
    
    /**
     * Process leftover submissions with flexible batching (allows partial batches)
     */
    private function processLeftoverBatches(array $validGwpTrx, SymfonyStyle $io): void
    {
        $batchSize = 50;
        $categories = ['SPICY_YUMSTER', 'PITCH_PURRFECT', 'DJ_FYRE', 'BLAZING_BITES'];
        
        // Get last processed category
        $processingStateRepo = $this->manager->getRepository(ProcessingState::class);
        $lastCategory = $processingStateRepo->getLastProcessedCategory('gwp_trx_leftover_processing');
        
        // Determine next category
        if ($lastCategory) {
            $lastIndex = array_search($lastCategory, $categories);
            $currentCategoryIndex = ($lastIndex + 1) % count($categories);
        } else {
            $currentCategoryIndex = 0; // Start with 'SPICY_YUMSTER'
        }
        
        $availableSubmissions = count($validGwpTrx);
        
        $io->writeln("🔄 Step 2: Processing leftover submissions");
        $io->writeln("📊 Available leftover submissions: {$availableSubmissions}");
        
        // Log the valid GwpTrx IDs in order
        $validIds = array_map(function($gwp) { return $gwp->getId(); }, $validGwpTrx);
        $io->writeln("📋 Valid leftover GwpTrx IDs in FIFO order: " . implode(', ', array_slice($validIds, 0, 20)) . (count($validIds) > 20 ? '...' : ''));
        
        if ($availableSubmissions == 0) {
            $io->writeln("⚠️  No valid leftover submissions available for processing.");
            return;
        }
        
        $io->writeln("🎯 Last processed category: {$lastCategory}");
        $io->writeln("🎯 Starting with category: {$categories[$currentCategoryIndex]}");
        
        $processedBatches = 0;
        $totalProductsAssigned = 0;
        $globalIndex = 0;
        $batchNum = 0;
        
        // For leftovers: Process all available submissions, allowing partial final batch
        while ($globalIndex < count($validGwpTrx)) {
            $batchNum++;
            $currentCategory = $categories[$currentCategoryIndex];
            
            // Calculate remaining submissions
            $remainingSubmissions = count($validGwpTrx) - $globalIndex;
            $isLastBatch = $remainingSubmissions <= $batchSize;
            
            $io->writeln("📦 Processing Batch {$batchNum} - Category: {$currentCategory}" . ($isLastBatch ? " (Final/Partial Batch)" : ""));
            
            $batchProcessed = 0;
            $batchAttempted = 0;
            $processedGwpTrx = [];
            $batchAssignedProducts = [];
            
            // Continue processing until we get 50 successful assignments OR run out of submissions
            while ($batchProcessed < $batchSize && $globalIndex < count($validGwpTrx)) {
                $gwpTrx = $validGwpTrx[$globalIndex];
                
                $submission = $this->manager->getRepository(Submission::class)->findOneBy(['id' => $gwpTrx->getSubId()]);
                
                $io->writeln("   🔄 Processing GwpTrx ID: {$gwpTrx->getId()}, Sub ID: {$gwpTrx->getSubId()}, Category: {$currentCategory} (Attempt " . ($batchAttempted + 1) . ")");
                
                $assignedProduct = $this->assignProductToSubmission($submission, $currentCategory, $gwpTrx, $io);
                if ($assignedProduct) {
                    $batchProcessed++;
                    $batchAssignedProducts[] = $assignedProduct;
                    $io->writeln("   ✅ Product assigned successfully (#{$batchProcessed}/" . ($isLastBatch ? "?" : $batchSize) . ")");
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
            
            // Flush batch
            $this->manager->flush();
            $io->writeln("✅ Batch {$batchNum} completed: {$batchProcessed} products assigned from {$batchAttempted} attempts");
            
            // Add to total
            $totalProductsAssigned += $batchProcessed;
            
            // Update last processed category
            $processingStateRepo->updateLastProcessedCategory('gwp_trx_leftover_processing', $currentCategory);
            
            // Move to next category
            $currentCategoryIndex = ($currentCategoryIndex + 1) % count($categories);
            $processedBatches++;
            
            // For leftover batches: Accept any completed batch (including partial final batch)
            if ($batchProcessed == 0 && !$isLastBatch) {
                $io->writeln("⚠️  Batch {$batchNum} assigned 0 products. Continuing to next batch...");
            }
        }
        
        $io->writeln("");
        $io->writeln("🎉 Leftover batch processing completed!");
        $io->writeln("📊 Total batches processed: {$processedBatches}");
        $io->writeln("📊 Total products assigned: {$totalProductsAssigned}");
        
        if ($totalProductsAssigned > 0) {
            $io->writeln("✅ Successfully assigned {$totalProductsAssigned} products from leftover submissions");
        } else {
            $io->writeln("⚠️  No products were assigned from leftover submissions");
        }
    }

    /**
     * Assign a product to a submission
     */
    private function assignProductToSubmission(Submission $submission, string $category, GwpTrx $gwpTrx, SymfonyStyle $io): ?Product
    {
        // CRITICAL: Re-check limits right before assignment to prevent race conditions
        if (!$this->passesLimitFilters($submission, $io, false, false)) {
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
        $productEntity->setProductSku($gwpTrx->getField7());
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