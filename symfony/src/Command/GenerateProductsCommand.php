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
    name: 'app:generate-products',
    description: 'Generates 30,000 product records in the database efficiently.',
)]
class GenerateProductsCommand extends Command
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
            ->addOption('method', 'm', InputOption::VALUE_OPTIONAL, 'Generation method: orm, dbal, or bulk', 'bulk')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Batch size for processing', 1000);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $method = $input->getOption('method');
        $batchSize = (int) $input->getOption('batch-size');

        $io->title('Starting Product Generation');
        $io->text(sprintf('Method: %s | Batch Size: %d', strtoupper($method), $batchSize));

        $categories = ['SPICY_YUMSTER', 'PITCH_PURRFECT', 'DJ_FYRE', 'BLAZING_BITES'];
        $productsPerCategory = 7500;
        $totalProducts = count($categories) * $productsPerCategory;

        // Initialize progress bar
        $progressBar = $io->createProgressBar($totalProducts);
        $progressBar->start();

        $startTime = microtime(true);

        switch ($method) {
            case 'bulk':
                $this->generateProductsBulk($categories, $productsPerCategory, $batchSize, $progressBar);
                break;
            case 'dbal':
                $this->generateProductsDBAL($categories, $productsPerCategory, $batchSize, $progressBar);
                break;
            case 'orm':
            default:
                $this->generateProductsORM($categories, $productsPerCategory, $batchSize, $progressBar);
                break;
        }

        $progressBar->finish();
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        $io->newLine(2);
        $io->success(sprintf(
            'Successfully generated %d product records in %s seconds using %s method!',
            $totalProducts,
            $executionTime,
            strtoupper($method)
        ));

        return Command::SUCCESS;
    }

    /**
     * Most efficient method using bulk SQL INSERT
     */
    private function generateProductsBulk(array $categories, int $productsPerCategory, int $batchSize, $progressBar): void
    {
        $sql = 'INSERT INTO product (product_code, product_category, product_type, product_name, is_locked, created_date) VALUES ';
        $values = [];
        $params = [];
        $paramIndex = 0;
        $currentDateTime = (new \DateTime())->format('Y-m-d H:i:s');

        foreach ($categories as $category) {
            $productName = str_replace('_', ' ', $category);

            for ($i = 0; $i < $productsPerCategory; $i++) {
                $runningNumber = 1001 + $i;
                $productCode = sprintf('%s_%d', $category, $runningNumber);

                $values[] = sprintf('(?, ?, ?, ?, ?, ?)');
                $params[] = $productCode;
                $params[] = $category;
                $params[] = 'PRODUCT';
                $params[] = $productName;
                $params[] = 0;
                $params[] = $currentDateTime;

                // Execute batch when we reach batch size
                if (count($values) >= $batchSize) {
                    $this->executeBulkInsert($sql, $values, $params);
                    $progressBar->advance(count($values));
                    
                    // Reset for next batch
                    $values = [];
                    $params = [];
                }
            }
        }

        // Execute remaining records
        if (!empty($values)) {
            $this->executeBulkInsert($sql, $values, $params);
            $progressBar->advance(count($values));
        }
    }

    /**
     * Efficient method using DBAL prepared statements
     */
    private function generateProductsDBAL(array $categories, int $productsPerCategory, int $batchSize, $progressBar): void
    {
        $sql = 'INSERT INTO product (product_code, product_category, product_type, product_name, is_locked, created_date) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $this->connection->prepare($sql);
        $currentDateTime = (new \DateTime())->format('Y-m-d H:i:s');

        $this->connection->beginTransaction();
        $count = 0;

        try {
            foreach ($categories as $category) {
                $productName = str_replace('_', ' ', $category);

                for ($i = 0; $i < $productsPerCategory; $i++) {
                    $runningNumber = 1001 + $i;
                    $productCode = sprintf('%s_%d', $category, $runningNumber);

                    $stmt->executeStatement([
                        $productCode,
                        $category,
                        'PRODUCT',
                        $productName,
                        0,
                        $currentDateTime
                    ]);

                    $count++;
                    $progressBar->advance();

                    // Commit in batches
                    if ($count % $batchSize === 0) {
                        $this->connection->commit();
                        $this->connection->beginTransaction();
                    }
                }
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Original ORM method with optimizations
     */
    private function generateProductsORM(array $categories, int $productsPerCategory, int $batchSize, $progressBar): void
    {
        // Disable SQL logging for better performance
        $this->connection->getConfiguration()->setSQLLogger(null);

        foreach ($categories as $category) {
            $productName = str_replace('_', ' ', $category);

            for ($i = 0; $i < $productsPerCategory; $i++) {
                $runningNumber = 1001 + $i;
                
                $product = new Product();
                $product->setProductCode(sprintf('%s_%d', $category, $runningNumber));
                $product->setProductCategory($category);
                $product->setProductType('PRODUCT');
                $product->setProductName($productName);
                $product->setIsLocked(0);
                $product->setCreatedDate(new \DateTime());

                $this->entityManager->persist($product);
                $progressBar->advance();

                // Flush and clear in batches
                if (($i + 1) % $batchSize === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }
        }
        
        // Final flush for any remaining objects
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function executeBulkInsert(string $baseSql, array $values, array $params): void
    {
        $sql = $baseSql . implode(', ', $values);
        $this->connection->executeStatement($sql, $params);
    }
}