<?php

namespace App\Command;

use App\Entity\GwpTrx;
use App\Entity\Product;
use App\Entity\Submission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:reset-gwp-trx')]
class ResetGwpTrx extends Command
{
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Reset GWP Trx data for clean slate processing');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            // Start a transaction
            $this->manager->getConnection()->beginTransaction();
            
            $io->writeln("🔄 Starting GWP Trx data reset...");
            
            // Step 1: Reset GwpTrx records
            $io->writeln("📝 Step 1: Resetting GwpTrx records...");
            $gwpTrxRepo = $this->manager->getRepository(GwpTrx::class);
            $allGwpTrx = $gwpTrxRepo->findAll();
            
            $gwpTrxCount = 0;
            foreach ($allGwpTrx as $gwpTrx) {
                $gwpTrx->setCompleted(false);
                $gwpTrx->setCompletedDate(null);
                $this->manager->persist($gwpTrx);
                $gwpTrxCount++;
            }
            
            $io->writeln("   ✅ Reset {$gwpTrxCount} GwpTrx records");
            
            // Step 2: Reset product assignments
            $io->writeln("📦 Step 2: Resetting product assignments...");
            $productRepo = $this->manager->getRepository(Product::class);
            
            // Find all products that are locked/assigned
            $assignedProducts = $productRepo->findBy(['is_locked' => 1]);
            
            $productCount = 0;
            foreach ($assignedProducts as $product) {
                // Reset product to unassigned state
                $product->setUser(null);
                $product->setLocked(false);
                $product->setLockedDate(null);
                $product->setDueDate(null);
                $product->setDeliveryStatus(null);
                $product->setReceiverFullname(null);
                $product->setReceiverMobileNo(null);
                $product->setAddress1(null);
                $product->setAddress2(null);
                $product->setCity(null);
                $product->setPostcode(null);
                $product->setState(null);
                $product->setDetailsUpdatedDate(null);
                $product->setUpdatedDate(new \DateTime());
                $product->setContacted(false);
                $product->setDeleted(false);
                $product->setSubId(null);
                
                $this->manager->persist($product);
                $productCount++;
            }
            
            $io->writeln("   ✅ Reset {$productCount} product assignments");
            
            // Step 3: Clear product references from submissions
            $io->writeln("📋 Step 3: Clearing product references from submissions...");
            $submissionRepo = $this->manager->getRepository(Submission::class);
            
            // Find all submissions with product references
            $submissionsWithProducts = $submissionRepo->createQueryBuilder('s')
                ->where('s.product_ref IS NOT NULL')
                ->andWhere('s.product_ref != :empty')
                ->setParameter('empty', '')
                ->getQuery()
                ->getResult();
            
            $submissionCount = 0;
            foreach ($submissionsWithProducts as $submission) {
                $submission->setProductRef(null);
                $this->manager->persist($submission);
                $submissionCount++;
            }
            
            $io->writeln("   ✅ Cleared product references from {$submissionCount} submissions");
            
            // Step 4: Clear field1 limit messages from submissions
            $io->writeln("🚫 Step 4: Clearing limit messages from submissions...");
            $submissionsWithLimits = $submissionRepo->createQueryBuilder('s')
                ->where('s.field1 LIKE :limit')
                ->setParameter('limit', 'LIMIT REACHED%')
                ->getQuery()
                ->getResult();
            
            $limitCount = 0;
            foreach ($submissionsWithLimits as $submission) {
                $submission->setField1(null);
                $this->manager->persist($submission);
                $limitCount++;
            }
            
            $io->writeln("   ✅ Cleared limit messages from {$limitCount} submissions");
            
            // Flush all changes
            $this->manager->flush();
            
            // Commit the transaction
            $this->manager->getConnection()->commit();
            
            $io->success([
                "GWP Trx data reset completed successfully!",
                "Summary:",
                "- Reset {$gwpTrxCount} GwpTrx records",
                "- Reset {$productCount} product assignments", 
                "- Cleared product references from {$submissionCount} submissions",
                "- Cleared limit messages from {$limitCount} submissions",
                "",
                "You can now run 'php bin/console app:process-gwp-trx' again with a clean slate."
            ]);

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
}