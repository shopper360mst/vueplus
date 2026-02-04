<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:reset-product')]
class ResetProductCommand extends Command
{
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Reset all products by nullifying delivery-related fields and setting is_locked to 0');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $this->manager->getConnection()->beginTransaction();
            
            $io->writeln("🔄 Starting product reset...");
            
            $products = $this->manager->getRepository(Product::class)->findAll();
            $totalCount = count($products);
            
            $io->writeln("Found {$totalCount} products to reset");
            
            foreach ($products as $product) {
                $product->setUser(null);
                $product->setLockedDate(null);
                $product->setReceiverFullName(null);
                $product->setReceiverMobileNo(null);
                $product->setAddress1(null);
                $product->setAddress2(null);
                $product->setCity(null);
                $product->setPostcode(null);
                $product->setState(null);
                $product->setCourierDetails(null);
                $product->setDeliveryStatus(null);
                $product->setDeliveredDate(null);
                $product->setDetailsUpdatedDate(null);
                $product->setUpdatedDate(null);
                $product->setContacted(null);
                $product->setDeleted(null);
                $product->setDueDate(null);
                $product->setSubId(null);
                $product->setCourierStatus(null);
                $product->setDeliveryAssign(null);
                $product->setLocked(false);
                
                $this->manager->persist($product);
            }
            
            $this->manager->flush();
            
            $this->manager->getConnection()->commit();
            
            $io->success("Reset {$totalCount} products successfully!");

        } catch (\Exception $e) {
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
