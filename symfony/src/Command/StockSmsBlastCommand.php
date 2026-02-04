<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Submission;
use App\Service\SmsBlastService;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:stock-sms-blast',
    description: 'Send SMS blast with carlsberg stock information to specific phone numbers'
)]
class StockSmsBlastCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private SmsBlastService $smsBlastService;
    private LoggerInterface $logger;

    // Placeholder phone numbers - replace with actual numbers
    private array $phoneNumbers = [
        '60123000845', // Replace with actual phone number 1
        '60182203477'  // Replace with actual phone number 2
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        SmsBlastService $smsBlastService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->smsBlastService = $smsBlastService;
        $this->logger = $logger;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('phone1', null, InputOption::VALUE_OPTIONAL, 'First phone number to send SMS to')
            ->addOption('phone2', null, InputOption::VALUE_OPTIONAL, 'Second phone number to send SMS to')
            ->setHelp('This command sends SMS with 1664 stock information to 2 specific phone numbers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Override default phone numbers if provided via options
            $phone1 = $input->getOption('phone1') ?? $this->phoneNumbers[0];
            $phone2 = $input->getOption('phone2') ?? $this->phoneNumbers[1];

            $io->title('1664 Stock SMS Blast');
            $io->text('Calculating stock information...');

            // Get stock available count (products where is_locked = 0)
            $stockAvailable = $this->getStockAvailable();
            $io->text("Stock available: {$stockAvailable}");

            // Get submissions to process count
            $toProcess = $this->getSubmissionsToProcess();
            $io->text("To process: {$toProcess}");

            // Calculate estimated balance
            $estBalance = $stockAvailable - $toProcess;
            $io->text("Estimated balance: {$estBalance}");

            // Prepare SMS message
            $message = "1664 stock : stock available = {$stockAvailable}, to process = {$toProcess}, est bal = {$estBalance}";

            $io->section('Sending SMS...');

            // Send SMS to both phone numbers
            $results = [];
            $phoneNumbers = [$phone1, $phone2];

            foreach ($phoneNumbers as $index => $phoneNumber) {
                $io->text("Sending to {$phoneNumber}...");
                
                try {
                    $response = $this->smsBlastService->smsBlast($phoneNumber, $message);
                    $results[] = [
                        'phone' => $phoneNumber,
                        'status' => 'success',
                        'response' => $response
                    ];
                    
                    $io->success("SMS sent successfully to {$phoneNumber}");
                    $this->logger->info("Stock SMS sent successfully", [
                        'phone' => $phoneNumber,
                        'message' => $message,
                        'response' => $response
                    ]);
                    
                } catch (\Exception $e) {
                    $results[] = [
                        'phone' => $phoneNumber,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    
                    $io->error("Failed to send SMS to {$phoneNumber}: " . $e->getMessage());
                    $this->logger->error("Failed to send stock SMS", [
                        'phone' => $phoneNumber,
                        'message' => $message,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Display summary
            $io->section('Summary');
            $io->table(
                ['Phone Number', 'Status', 'Response/Error'],
                array_map(function($result) {
                    return [
                        $result['phone'],
                        $result['status'],
                        $result['status'] === 'success' ? $result['response'] : $result['error']
                    ];
                }, $results)
            );

            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
            $totalCount = count($results);

            if ($successCount === $totalCount) {
                $io->success("All SMS messages sent successfully ({$successCount}/{$totalCount})");
                return Command::SUCCESS;
            } elseif ($successCount > 0) {
                $io->warning("Some SMS messages sent successfully ({$successCount}/{$totalCount})");
                return Command::SUCCESS;
            } else {
                $io->error("All SMS messages failed to send");
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            $this->logger->error('Stock SMS blast command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Get count of available stock (products where is_locked = 0)
     */
    private function getStockAvailable(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(p.id)')
           ->from(Product::class, 'p')
           ->where('p.is_locked = :isLocked')
           ->setParameter('isLocked', false);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get count of submissions to process
     * SELECT COUNT(id) FROM submission WHERE submit_code = 'GWP' and submit_type <> 'in-store' 
     * AND ((STATUS = 'approved' AND product_ref IS NULL AND FIELD1 IS null) OR STATUS = 'processing')
     */
    private function getSubmissionsToProcess(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(s.id)')
           ->from(Submission::class, 's')
           ->where('s.submit_code = :submitCode')
           ->andWhere('s.submit_type != :excludeSubmitType')
           ->andWhere(
               $qb->expr()->orX(
                   $qb->expr()->andX(
                       $qb->expr()->eq('s.status', ':approvedStatus'),
                       $qb->expr()->isNull('s.product_ref'),
                       $qb->expr()->isNull('s.field1')
                   ),
                   $qb->expr()->eq('s.status', ':processingStatus')
               )
           )
           ->setParameter('submitCode', 'GWP')
           ->setParameter('excludeSubmitType', 'in-store')
           ->setParameter('approvedStatus', 'approved')
           ->setParameter('processingStatus', 'processing');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}