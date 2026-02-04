<?php

namespace App\Command;

use App\Entity\Submission;
use Psr\Log\LoggerInterface;
use App\Service\MailerService;
use App\Service\ActivityService;
use App\Service\CurlToUrlService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:csv-to-diy-blast',
    description: 'Read CSV file and send submissions to DIY'
)]
class CsvToDiyBlastCommand extends Command
{
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $paramBag,
        private CurlToUrlService $cts,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file')
            ->addOption('dry-run', 'd', null, 'Verify all entries without sending to DIY');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $dryRun = $input->getOption('dry-run');

        if (!file_exists($filePath)) {
            $io->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        if (!$this->paramBag->get('app.to_diy')) {
            $io->error("DIY integration is not enabled (app.to_diy is false)");
            return Command::FAILURE;
        }

        try {
            $rows = $this->readCsv($filePath);
            
            if (empty($rows)) {
                $io->error('No data found in CSV file');
                return Command::FAILURE;
            }

            $io->title('CSV to DIY Blast Command');
            if ($dryRun) {
                $io->note('DRY RUN MODE - Verifying entries without sending to DIY');
            }
            $io->info("Processing " . count($rows) . " rows from CSV");

            $stats = $this->processRows($rows, $io, $dryRun);

            $io->newLine();
            $io->success('Processing completed');
            $io->writeln([
                '<fg=bright-green>Total rows: ' . $stats['total'] . '</>',
                '<fg=bright-green>Sent to DIY: ' . $stats['sent'] . '</>',
                '<fg=bright-yellow>Not found in DB: ' . $stats['not_found'] . '</>',
                '<fg=bright-red>Errors: ' . $stats['errors'] . '</>',
            ]);

            if (!empty($stats['error_details'])) {
                $io->writeln(['<fg=bright-red>Error Details:</>']);
                foreach ($stats['error_details'] as $error) {
                    $io->writeln(['<fg=bright-red>  - ' . $error . '</>']);
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            $this->logger->error('CsvToDiyBlastCommand error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function readCsv(string $filePath): array
    {
        $rows = [];
        $file = fopen($filePath, 'r');
        
        if ($file === false) {
            throw new \RuntimeException("Cannot open file: {$filePath}");
        }

        $headerSkipped = false;
        while (($row = fgetcsv($file)) !== false) {
            if (!$headerSkipped) {
                $headerSkipped = true;
                continue;
            }
            
            if (!empty($row[0])) {
                $rows[] = [
                    'id' => trim($row[0]),
                    'submit_code' => trim($row[1] ?? ''),
                    'submit_type' => trim($row[2] ?? ''),
                    'created_date' => trim($row[3] ?? ''),
                ];
            }
        }
        
        fclose($file);
        return $rows;
    }

    private function processRows(array $rows, SymfonyStyle $io, bool $dryRun = false): array
    {
        $stats = [
            'total' => count($rows),
            'sent' => 0,
            'not_found' => 0,
            'errors' => 0,
            'error_details' => []
        ];

        $progressBar = $io->createProgressBar($stats['total']);
        $progressBar->start();

        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $this->paramBag->get('app.diy_integration_key')
        ];

        foreach ($rows as $rowData) {
            try {
                $submissionId = (int) $rowData['id'];
                $submission = $this->entityManager->getRepository(Submission::class)->findOneBy([
                    'id' => $submissionId
                ]);

                if (!$submission) {
                    $stats['not_found']++;
                    $progressBar->advance();
                    continue;
                }

                $questId = $this->convertFieldtoQuest($submission->getSubmitType());
                
                if (!$questId) {
                    $stats['errors']++;
                    $stats['error_details'][] = "ID {$submissionId}: Unable to determine quest ID from submit_type '{$submission->getSubmitType()}'";
                    $progressBar->advance();
                    continue;
                }

                $postData = $this->buildPostData($submission, $questId);

                if ($dryRun) {
                    $stats['sent']++;
                    $this->logger->info("[DRY RUN] Would send submission {$submissionId} to DIY");
                } else {
                    $response = $this->cts->curlToUrl(
                        $this->paramBag->get('app.diy_whatsapp_api') . 'submission',
                        null,
                        true,
                        $postData,
                        $headers
                    );

                    if ($response) {
                        $stats['sent']++;
                        $this->logger->info("Sent submission {$submissionId} to DIY");
                    } else {
                        $stats['errors']++;
                        $stats['error_details'][] = "ID {$submissionId}: Failed to send to DIY";
                        $this->logger->error("Failed to send submission {$submissionId} to DIY");
                    }
                }

            } catch (\Exception $e) {
                $stats['errors']++;
                $stats['error_details'][] = "ID {$rowData['id']}: " . $e->getMessage();
                $this->logger->error("Error processing ID {$rowData['id']}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        return $stats;
    }

    private function buildPostData(Submission $submission, string $questId): array
    {
        $receiptUrl = '';
        if ($this->paramBag->get('app.s3_secret_key') != "") {
            $receiptUrl = $this->paramBag->get('app.s3_base_url') . $this->paramBag->get('app.s3_bucket_name') . '/' . $submission->getAttachment();
        } else {
            $receiptUrl = $this->paramBag->get('app.base_url') . 'images/uploaded/receipt/' . $submission->getAttachment();
        }

        $postData = [
            'contest_id' => $questId,
            'sub_id' => $submission->getId(),
            'full_name' => $submission->getFullName(),
            'mobile_number' => $submission->getMobileNo(),
            'email_address' => $submission->getEmail(),
            'mykad' => $submission->getNationalId(),
        ];

        if ((string)$questId === '152' || (int)$questId === 152) {
            $postData['receipt_no'] = $submission->getAttachmentNo();
            $postData['receipt'] = $receiptUrl;
            $postData['gwp'] = $submission->getField10();
            $postData['state_del'] = $submission->getState();
        } else {
            $postData['receipt'] = $receiptUrl;
        }

        return $postData;
    }

    private function convertFieldtoQuest(string $channel): ?string
    {
        $mapping = [
            'MONT' => 'app.integration_id1',
            'PUBS/BARS' => 'app.integration_id1',
            'IN-STORE' => 'app.integration_id1',
            'CONVENIENCE STORE' => 'app.integration_id2',
            'CVSTOFT' => 'app.integration_id5',
            'SUPER/HYPERMARKET/99' => 'app.integration_id3',
            'SUPER/HYPERMARKET' => 'app.integration_id3',
            '99 SPEEDMART' => 'app.integration_id3',
            '99SM' => 'app.integration_id3',
            'S99' => 'app.integration_id3',
            'SHM_WM' => 'app.integration_id3',
            'SHM_EM' => 'app.integration_id3',
            'E-COMMERCE' => 'app.integration_id2',
            'ECOMM' => 'app.integration_id2',
            'TONT' => 'app.integration_id4',
        ];

        $paramKey = $mapping[strtoupper($channel)] ?? null;
        
        if ($paramKey) {
            return $this->paramBag->get($paramKey);
        }

        return null;
    }
}
