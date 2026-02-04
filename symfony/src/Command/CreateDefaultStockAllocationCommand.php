<?php

namespace App\Command;

use App\Entity\StockAllocation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'app:create-default-stock-allocation')]
class CreateDefaultStockAllocationCommand extends Command
{
    public function __construct(private EntityManagerInterface $manager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create default stock allocation data')
            ->setHelp('This command creates default stock allocation entries for each week based on predefined values.')
            ->addOption(
                'truncate',
                't',
                InputOption::VALUE_NONE,
                'Truncate the stock_allocation table before inserting new data'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force execution without confirmation'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Default GWP Stock List - equivalent to your original array
        $GWPStockList = [1111, 1111, 1111, 1111, 1111, 1111, 1111, 1111, 1112];
        
        $io->title('Create Default Stock Allocation');
        $io->text('This will create stock allocation entries for ' . count($GWPStockList) . ' weeks.');
        
        // Show what will be created
        $io->section('Stock Allocation Preview:');
        $table = [];
        foreach ($GWPStockList as $weekNumber => $stockAmount) {
            $table[] = [
                'Week ' . ($weekNumber + 1),
                number_format($stockAmount)
            ];
        }
        $io->table(['Week', 'Stock Amount'], $table);

        // Handle truncate option
        if ($input->getOption('truncate')) {
            $io->warning('This will TRUNCATE the stock_allocation table and remove all existing data!');
            
            if (!$input->getOption('force')) {
                $confirmed = $io->ask('Type CONFIRM to execute truncation');
                if ($confirmed !== 'CONFIRM') {
                    $io->error('Operation cancelled.');
                    return Command::FAILURE;
                }
            }
            
            // Truncate table
            $connection = $this->manager->getConnection();
            $connection->executeStatement('TRUNCATE TABLE stock_allocation');
            $io->success('Stock allocation table truncated.');
        } else {
            // Check for existing data
            $existingCount = $this->manager->getRepository(StockAllocation::class)
                ->createQueryBuilder('sa')
                ->select('COUNT(sa.id)')
                ->getQuery()
                ->getSingleScalarResult();
                
            if ($existingCount > 0) {
                $io->warning("Found {$existingCount} existing stock allocation records.");
                
                if (!$input->getOption('force')) {
                    $continue = $io->confirm('Do you want to continue? (This may create duplicates)', false);
                    if (!$continue) {
                        $io->info('Operation cancelled.');
                        return Command::SUCCESS;
                    }
                }
            }
        }

        // Final confirmation if not forced
        if (!$input->getOption('force')) {
            $confirmed = $io->confirm('Proceed with creating stock allocation entries?', true);
            if (!$confirmed) {
                $io->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Create stock allocation entries
        $io->progressStart(count($GWPStockList));
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($GWPStockList as $index => $stockAmount) {
            $weekNumber = $index + 1; // Week numbers start from 1
            
            try {
                // Check if week already exists (unless we truncated)
                if (!$input->getOption('truncate')) {
                    $existing = $this->manager->getRepository(StockAllocation::class)
                        ->findByWeekNumber($weekNumber);
                    
                    if ($existing) {
                        $skipped++;
                        $io->progressAdvance();
                        continue;
                    }
                }

                // Create new stock allocation
                $stockAllocation = new StockAllocation();
                $stockAllocation->setWeekNumber($weekNumber);
                $stockAllocation->setStockAmount($stockAmount);
                $stockAllocation->setCreatedDate(new \DateTime());
                
                $this->manager->persist($stockAllocation);
                $created++;
                
            } catch (\Exception $e) {
                $errors[] = "Week {$weekNumber}: " . $e->getMessage();
            }
            
            $io->progressAdvance();
        }

        // Flush all changes
        try {
            $this->manager->flush();
            $io->progressFinish();
            
            // Show results
            $io->success("Stock allocation creation completed!");
            $io->definitionList(
                ['Created' => $created],
                ['Skipped' => $skipped],
                ['Errors' => count($errors)]
            );
            
            if (!empty($errors)) {
                $io->section('Errors:');
                foreach ($errors as $error) {
                    $io->error($error);
                }
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->progressFinish();
            $io->error('Failed to save stock allocations: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}