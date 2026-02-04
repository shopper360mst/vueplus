<?php

namespace App\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Entity\Product;
use App\Entity\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

#[AsCommand(name: 'app:create-sample-product', description: 'Creates sample product data from an Excel file')]
class CreateSampleProduct extends Command
{
    //protected static $defaultName = 'app:create-sample-product';
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
        ->addArgument('excelFile', InputArgument::OPTIONAL, 'Excel Path Required: /excel/sample-product.xlsx')
    ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // To get Parameter use 
        $excelFile = "/excel/sample-product.xlsx";
        if (!file_exists(__DIR__.$excelFile)) {
            $output->writeln([
                '',
                'Error in finding sample-product excel file. Check your filename and path.'
            ]);
            return Command::SUCCESS;
        }

        try {            
            $reader = ReaderEntityFactory::createReaderFromFile($excelFile);
            $reader->open(__DIR__.$excelFile);
            $rowIndex = 0;
            $processedRows = 0;
            $batchSize = 1000; // Optimized batch size for 30k records
            
            // Disable SQL logging for better performance
            $this->manager->getConnection()->getConfiguration()->setSQLLogger(null);
            
            //inputParameterValue, originally is from a prompt now defaulted for ease of batch test use.
            $inputParameterValue = "y";

            $output->writeln('<info>Starting to process Excel file...</info>');
            $startTime = microtime(true);

            foreach ($reader->getSheetIterator() as $sheet) {
                $this->manager->getConnection()->beginTransaction();
                
                foreach ($sheet->getRowIterator() as $row) {
                    $cellsPerRow = $row->getCells();
                    
                    // Skip header row
                    if ($rowIndex == 0) {
                        $rowIndex++;
                        continue;
                    }
                    
                    $_PRODUCT_CODE = $cellsPerRow[0]->getValue();
                    $_PRODUCT_CATE = $cellsPerRow[1]->getValue();
                    $_PRODUCT_TYPE = $cellsPerRow[2]->getValue();
                    $_PRODUCT_NAME = $cellsPerRow[3]->getValue();

                    if ($inputParameterValue == "y") {
                        try {                        
                            $targetEntity = new Product();
                            $targetEntity->setProductCode($_PRODUCT_CODE);
                            $targetEntity->setProductCategory($_PRODUCT_CATE);
                            $targetEntity->setProductType($_PRODUCT_TYPE);
                            $targetEntity->setProductName($_PRODUCT_NAME);
                            $targetEntity->setLocked(0);
                            $targetEntity->setCreatedDate(new \DateTime());
                            
                            $this->manager->persist($targetEntity);
                            $processedRows++;
                            
                            // Process in batches for optimal performance
                            if ($processedRows % $batchSize === 0) {
                                $this->manager->flush();
                                $this->manager->clear(); // Clear memory
                                
                                // Commit current transaction and start new one
                                $this->manager->getConnection()->commit();
                                $this->manager->getConnection()->beginTransaction();
                                
                                $output->writeln(sprintf(
                                    '<comment>Processed %d records (Batch #%d)</comment>',
                                    $processedRows,
                                    ceil($processedRows / $batchSize)
                                ));
                                
                                // Show progress and memory usage
                                $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
                                $output->writeln(sprintf('<info>Memory usage: %s MB</info>', $memoryUsage));
                            }
                        
                        } catch (\Exception $e) {
                            $this->manager->getConnection()->rollBack();                            
                            $output->writeln([
                                '',
                                '<error>Error processing row ' . ($rowIndex + 1) . ': ' . $e->getMessage() . '</error>'
                            ]);
                            return Command::FAILURE;
                        }
                    }
                    
                    $rowIndex++;
                }
                
                // Flush remaining records
                if ($processedRows % $batchSize !== 0) {
                    $this->manager->flush();
                    $this->manager->clear();
                }
                
                $this->manager->getConnection()->commit();
                
                $endTime = microtime(true);
                $executionTime = round($endTime - $startTime, 2);
                
                $output->writeln([
                    '',
                    '<fg=bright-green>✓ Successfully processed ' . $processedRows . ' records</fg=bright-green>',
                    '<info>Execution time: ' . $executionTime . ' seconds</info>',
                    '<info>Average: ' . round($processedRows / $executionTime, 2) . ' records/second</info>',
                    '',
                    '<fg=bright-yellow>To check the imported data, use this query:</fg=bright-yellow>',
                    '<fg=bright-cyan>SELECT id, product_code, product_category, product_type, product_name, created_date FROM product ORDER BY id DESC LIMIT 10;</fg=bright-cyan>',
                    ''
                ]);
            }
            
            $reader->close();
            
        } catch (\Exception $e) {
            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }
            $output->writeln([
                '',
                '<error>Error processing the Excel file: ' . $e->getMessage() . '</error>'
            ]);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
