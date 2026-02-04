<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-regional-products',
    description: 'Generates products by category with customizable WM and EM region quantities.',
)]
class GenerateRegionalProductsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
    }

    protected function configure(): void
    {
        $this
            ->addOption('category', 'c', InputOption::VALUE_REQUIRED, 'Product category (LUGGAGE_SHM, RUMMY_SHM, GRILL_SHM)')
            ->addOption('wm', null, InputOption::VALUE_REQUIRED, 'Number of products for WM region', 0)
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'Number of products for EM region', 0)
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Batch size for processing', 1000);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $category = strtoupper($input->getOption('category'));
        $wmCount = (int) $input->getOption('wm');
        $emCount = (int) $input->getOption('em');
        $batchSize = (int) $input->getOption('batch-size');

        $allowedCategories = ['LUGGAGE_SHM', 'RUMMY_SHM', 'GRILL_SHM'];
        
        if (!in_array($category, $allowedCategories)) {
            $io->error(sprintf('Invalid category. Allowed: %s', implode(', ', $allowedCategories)));
            return Command::FAILURE;
        }

        if ($wmCount <= 0 && $emCount <= 0) {
            $io->error('At least one region (WM or EM) must have a quantity greater than 0');
            return Command::FAILURE;
        }

        $totalProducts = $wmCount + $emCount;
        
        $io->title('Starting Regional Product Generation');
        $io->table(
            ['Parameter', 'Value'],
            [
                ['Category', $category],
                ['WM Region', $wmCount],
                ['EM Region', $emCount],
                ['Total Products', $totalProducts],
                ['Batch Size', $batchSize]
            ]
        );

        $progressBar = $io->createProgressBar($totalProducts);
        $progressBar->start();

        $startTime = microtime(true);

        try {
            $this->generateProducts($category, $wmCount, $emCount, $batchSize, $progressBar);
            
            $progressBar->finish();
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $io->newLine(2);
            $io->success(sprintf(
                'Successfully generated %d products for %s (WM: %d, EM: %d) in %s seconds!',
                $totalProducts,
                $category,
                $wmCount,
                $emCount,
                $executionTime
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error generating products: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function generateProducts(string $category, int $wmCount, int $emCount, int $batchSize, $progressBar): void
    {
        $sql = 'INSERT INTO product (product_code, product_category, product_type, product_name, region, is_locked, created_date) VALUES ';
        $values = [];
        $params = [];
        $currentDateTime = (new \DateTime())->format('Y-m-d H:i:s');
        $productName = str_replace('_', ' ', $category);

        $runningNumber = 1001;

        foreach (['WM' => $wmCount, 'EM' => $emCount] as $region => $count) {
            for ($i = 0; $i < $count; $i++) {
                $productCode = sprintf('%s_%s_%d', $category, $region, $runningNumber);

                $values[] = '(?, ?, ?, ?, ?, ?, ?)';
                $params[] = $productCode;
                $params[] = $category;
                $params[] = 'PRODUCT';
                $params[] = $productName;
                $params[] = $region;
                $params[] = 0;
                $params[] = $currentDateTime;

                $runningNumber++;

                if (count($values) >= $batchSize) {
                    $this->executeBulkInsert($sql, $values, $params);
                    $progressBar->advance(count($values));
                    
                    $values = [];
                    $params = [];
                }
            }
        }

        if (!empty($values)) {
            $this->executeBulkInsert($sql, $values, $params);
            $progressBar->advance(count($values));
        }
    }

    private function executeBulkInsert(string $baseSql, array $values, array $params): void
    {
        $sql = $baseSql . implode(', ', $values);
        $this->connection->executeStatement($sql, $params);
    }
}
