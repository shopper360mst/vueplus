<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Submission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:fix-orphaned-products')]
class FixOrphanedProducts extends Command
{
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Fix orphaned products that are locked but not properly assigned to submissions');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            // Start a transaction
            $this->manager->getConnection()->beginTransaction();
            
            $io->writeln("🔍 Searching for orphaned products...");
            
            // Find all locked products
            $lockedProducts = $this->manager->getRepository(Product::class)->findBy(['is_locked' => true]);
            $totalLocked = count($lockedProducts);
            
            $io->writeln("Found {$totalLocked} locked products");
            
            $orphanedCount = 0;
            $validCount = 0;
            
            foreach ($lockedProducts as $product) {
                $productId = $product->getId();
                $submission = $product->getSubId();
                
                if (!$submission) {
                    // Product is locked but has no sub_id - definitely orphaned
                    $this->unlockProduct($product, $io, "No sub_id");
                    $orphanedCount++;
                    continue;
                }
                
                if (!$submission->getProductRef()) {
                    // Submission has no product_ref - orphaned
                    $this->unlockProduct($product, $io, "Submission {$submission->getId()} has no product_ref");
                    $orphanedCount++;
                    continue;
                }
                
                // Check if this product ID is in the comma-separated product_ref
                $productIds = explode(',', $submission->getProductRef());
                $productIds = array_map('trim', $productIds);
                
                if (!in_array((string)$productId, $productIds)) {
                    // Product is not in submission's product_ref - orphaned
                    $this->unlockProduct($product, $io, "Product {$productId} not in submission {$submission->getId()} product_ref: {$submission->getProductRef()}");
                    $orphanedCount++;
                } else {
                    // Product is properly assigned
                    $validCount++;
                }
            }
            
            // Flush all changes
            $this->manager->flush();
            
            // Commit the transaction
            $this->manager->getConnection()->commit();
            
            $io->writeln("📊 Summary:");
            $io->writeln("   Total locked products: {$totalLocked}");
            $io->writeln("   Valid assignments: {$validCount}");
            $io->writeln("   Orphaned products fixed: {$orphanedCount}");
            
            if ($orphanedCount > 0) {
                $io->success("Fixed {$orphanedCount} orphaned products!");
            } else {
                $io->success("No orphaned products found - all assignments are valid!");
            }

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
    
    private function unlockProduct(Product $product, SymfonyStyle $io, string $reason): void
    {
        $productId = $product->getId();
        $category = $product->getProductCategory();
        
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
        
        $io->writeln("🔓 Unlocked Product ID: {$productId} (Category: {$category}) - Reason: {$reason}");
    }
}