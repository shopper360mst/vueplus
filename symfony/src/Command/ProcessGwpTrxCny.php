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

#[AsCommand(name: 'app:process-gwp-trx-cny')]
class ProcessGwpTrxCny extends Command
{

    private $passwordEncoder;
    private $manager;
    private $wmDeliveryCounter = 0;
    private $luggageWmDeliveryCounter = 0;
    
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
            $failedCount = 0;
            
            $io->writeln("🔍 Step 1: Filtering submissions based on limits...");
            $progressBar = $io->createProgressBar($totalCount);
            
            foreach ($allGwpTrx as $gwpTrx) {
                $submission = $this->manager->getRepository(Submission::class)->findOneBy(['id' => $gwpTrx->getSubId()]);
                
                if (!$submission) {
                    $gwpTrx->setCompleted(true);
                    $gwpTrx->setCompletedDate(new \DateTime());
                    $this->manager->persist($gwpTrx);
                    $failedCount++;
                    $processedCount++;
                    $progressBar->advance();
                    continue;
                }
                
                // Update field7 from GwpTrx
                $submission->setField7($gwpTrx->getField7() ?: '');
                $this->manager->persist($submission);
                
                // Check if submission passes all limit filters (validate without verbose output)
                if ($this->passesLimitFilters($submission, $io, false)) {
                    $validSubmissions[] = $gwpTrx;
                } else {
                    // Mark as completed if it doesn't pass filters
                    $gwpTrx->setCompleted(true);
                    $gwpTrx->setCompletedDate(new \DateTime());
                    $this->manager->persist($gwpTrx);
                    $failedCount++;
                }
                
                $processedCount++;
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $io->newLine(2);
            
            // Flush the changes from Step 1 (marking failed records as completed)
            $this->manager->flush();
            
            $validCount = count($validSubmissions);
            $io->writeln("✅ Step 1 Complete: {$validCount} passed, {$failedCount} rejected from {$totalCount} records");
            
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
     * Validates that national_id and mobile_no are present
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
        
        if ($verbose) {
            $io->writeln("  ✅ Submission ID: {$submission->getId()} passed basic validation");
        }
        return true;
    }
    
    /**
     * Process valid submissions and assign products based on product_redeem
     */
    private function processBatches(array $validGwpTrx, SymfonyStyle $io): void
    {
        $availableSubmissions = count($validGwpTrx);
        
        $io->writeln("🔄 Step 2: Processing product assignments...");
        
        // Log the valid GwpTrx IDs in order
        $validIds = array_map(function($gwp) { return $gwp->getId(); }, $validGwpTrx);
        $io->writeln("📋 Valid GwpTrx IDs in FIFO order: " . implode(', ', array_slice($validIds, 0, 20)) . (count($validIds) > 20 ? '...' : ''));
        
        if ($availableSubmissions == 0) {
            $io->writeln("⚠️  No valid submissions available for processing.");
            return;
        }
        
        $io->writeln("📊 Available submissions: {$availableSubmissions}");
        
        $totalProductsAssigned = 0;
        $totalProcessed = 0;
        $totalSkipped = 0;
        $progressBar = $io->createProgressBar($availableSubmissions);
        
        foreach ($validGwpTrx as $gwpTrx) {
            $submission = $this->manager->getRepository(Submission::class)->findOneBy(['id' => $gwpTrx->getSubId()]);
            
            if (!$submission) {
                $gwpTrx->setCompleted(true);
                $gwpTrx->setCompletedDate(new \DateTime());
                $this->manager->persist($gwpTrx);
                $progressBar->advance();
                $totalProcessed++;
                $totalSkipped++;
                continue;
            }
            
            // Get allowed categories from product_redeem
            $productRedeem = $gwpTrx->getProductRedeem(); // e.g., "LUGGAGE_SHM, RUMMY_SHM, GRILL_SHM"
            if (!$productRedeem) {
                $gwpTrx->setCompleted(true);
                $gwpTrx->setCompletedDate(new \DateTime());
                $this->manager->persist($gwpTrx);
                $progressBar->advance();
                $totalProcessed++;
                $totalSkipped++;
                continue;
            }
            
            // Parse allowed categories
            $allowedCategories = array_map('trim', explode(',', $productRedeem));
            
            // Try to assign product from allowed categories
            $productAssigned = false;
            foreach ($allowedCategories as $category) {
                // Check if this product family has already been claimed
                if ($this->hasFamilyBeenClaimed($submission, $category, $io)) {
                    continue; // Skip this category, try next
                }
                
                // Try to find and assign product
                $assignedProduct = $this->assignProductToSubmission($submission, $category, $io);
                if ($assignedProduct) {
                    $productAssigned = true;
                    $totalProductsAssigned++;
                }
            }
            
            // Mark GwpTrx as completed regardless of assignment success
            $gwpTrx->setCompleted(true);
            $gwpTrx->setCompletedDate(new \DateTime());
            $this->manager->persist($gwpTrx);
            
            $progressBar->advance();
            $totalProcessed++;
        }
        
        $progressBar->finish();
        $io->newLine(2);
        
        // Final flush to persist all changes
        $this->manager->flush();
        
        $io->writeln("🎉 Processing completed");
        $io->writeln("📊 Products assigned: {$totalProductsAssigned}/{$totalProcessed}");
        if ($totalSkipped > 0) {
            $io->writeln("⏭️  Skipped: {$totalSkipped} (missing submission or product_redeem)");
        }
    }
    
    /**
     * Check if product family (LUGGAGE, RUMMY, GRILL) has already been claimed by this national_id or mobile_no
     * Optimized to use targeted query instead of loading all APPROVED submissions
     */
    private function hasFamilyBeenClaimed(Submission $submission, string $category, SymfonyStyle $io): bool
    {
        // Extract family and region from category: "LUGGAGE_SHM_WM" -> family: "LUGGAGE", region: "WM"
        $parts = explode('_', $category);
        $family = $parts[0]; // LUGGAGE, RUMMY, or GRILL
        
        // Check if region is specified (WM or EM)
        $region = null;
        if (count($parts) >= 3) {
            $lastPart = strtolower(end($parts));
            if ($lastPart === 'wm' || $lastPart === 'em') {
                $region = $lastPart;
            }
        }
        
        $nationalId = $submission->getNationalId();
        $mobileNo = $submission->getMobileNo();
        
        // Optimized query: Only fetch APPROVED submissions with matching identifiers and products
        $qb = $this->manager->getRepository(Submission::class)->createQueryBuilder('s');
        $matchingSubmissions = $qb
            ->where('s.status = :status')
            ->andWhere('(s.national_id = :nationalId OR s.mobile_no = :mobileNo)')
            ->andWhere('s.product_ref IS NOT NULL')
            ->setParameter('status', 'APPROVED')
            ->setParameter('nationalId', $nationalId)
            ->setParameter('mobileNo', $mobileNo)
            ->getQuery()
            ->getResult();
        
        foreach ($matchingSubmissions as $approvedSub) {
            $productRef = $approvedSub->getProductRef();
            if (!$productRef) {
                continue;
            }
            
            // Get product IDs from this submission
            $productIds = explode(',', $productRef);
            $productIds = array_map('trim', $productIds);
            
            // Check if any of these products belong to the same family (regardless of region)
            foreach ($productIds as $productId) {
                if (!$productId) {
                    continue;
                }
                $product = $this->manager->getRepository(Product::class)->find($productId);
                if ($product) {
                    $productCategory = $product->getProductCategory();
                    if ($productCategory) {
                        $productFamily = explode('_', $productCategory)[0];
                        if ($productFamily === $family) {
                            return true; // Family already claimed (blocks all regions of this family)
                        }
                    }
                }
            }
        }
        
        return false; // Family not claimed yet
    }


    /**
     * Assign a product to a submission
     * Does not flush - parent loop handles flush to maintain transaction integrity
     */
    private function assignProductToSubmission(Submission $submission, string $category, SymfonyStyle $io): ?Product
    {
        // Validate submission has required fields
        if (!$submission->getReceiverFullname() || !$submission->getReceiverMobileNo()) {
            return null;
        }
        
        // Parse category and region from the input (e.g., "LUGGAGE_SHM_WM" -> category: "LUGGAGE_SHM", region: "WM")
        $parts = explode('_', $category);
        $region = null;
        $actualCategory = $category;
        
        if (count($parts) >= 3) {
            $lastPart = strtolower(end($parts));
            if ($lastPart === 'wm' || $lastPart === 'em') {
                $region = $lastPart;
                array_pop($parts);
                $actualCategory = implode('_', $parts);
            }
        }
        
        // Find and lock a product in the specified category and region
        $foundProductId = $this->manager->getRepository(Product::class)->findAndLock($submission->getUser(), $actualCategory, $region);
        
        if (!$foundProductId) {
            return null;
        }
        
        $productEntity = $this->manager->getRepository(Product::class)->find($foundProductId);
        if (!$productEntity) {
            return null;
        }
        
        // Set due date to 5 calendar days from locked_date
        $lockedDate = $productEntity->getLockedDate();
        if ($lockedDate) {
            $dueDate = $this->addCalendarDays($lockedDate, 5);
            $productEntity->setDueDate($dueDate);
        }
        
        // Set delivery_assign based on region
        if ($region === 'wm') {
            // Extract family from category for ratio assignment
            $categoryParts = explode('_', $actualCategory);
            $family = $categoryParts[0];
            
            if ($family === 'LUGGAGE') {
                // LUGGAGE: 1:2 ratio (TS:SMX) - cycle through TS, SMX, SMX
                $mod = $this->luggageWmDeliveryCounter % 3;
                $deliveryAssign = ($mod === 0) ? 'TS' : 'SMX';
                $this->luggageWmDeliveryCounter++;
            } else {
                // Other families: 1:1 alternation
                $deliveryAssign = ($this->wmDeliveryCounter % 2 === 0) ? 'TS' : 'SMX';
                $this->wmDeliveryCounter++;
            }
            $productEntity->setDeliveryAssign($deliveryAssign);
        } elseif ($region === 'em') {
            $productEntity->setDeliveryAssign('GDEX');
        }
        
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
        
        // NOTE: Flush is called once per batch in processBatches() to maintain transaction integrity
        // This allows rollback of entire batch if needed
        
        return $productEntity;
    }
}