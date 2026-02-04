<?php

namespace App\Command;

use App\Entity\Payload;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(name: 'app:getpayload', description: 'Display payload data by ID in table format')]
class GetPayloadCommand extends Command
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Payload ID to retrieve');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $payloadId = $input->getArgument('id');

        // Validate that ID is numeric
        if (!is_numeric($payloadId)) {
            $io->error('Payload ID must be a number.');
            return Command::FAILURE;
        }

        // Find the payload by ID
        $payload = $this->entityManager->getRepository(Payload::class)->find($payloadId);

        if (!$payload) {
            $io->error(sprintf('Payload with ID %s not found.', $payloadId));
            return Command::FAILURE;
        }

        // Clear screen
        $output->write("\033[2J\033[H");
        
        $io->title(sprintf('Payload Details (ID: %s)', $payloadId));
        
        // Display payload metadata
        $io->section('Payload Information');
        $io->writeln(sprintf('Created Date: %s', $payload->getCreatedDate() ? $payload->getCreatedDate()->format('Y-m-d H:i:s') : 'N/A'));
        $io->writeln(sprintf('Score: %s', $payload->getScore() ?? 'N/A'));
        $io->writeln(sprintf('Status: %s', $payload->getStatus() ?? 'N/A'));
        
        // Display payload content
        $io->section('Payload Content');
        $io->writeln($this->formatPayloadAsJson($payload->getPayload()));

        $io->success('Payload data retrieved successfully.');
        
        return Command::SUCCESS;
    }

    /**
     * Format payload data as PHP array representation
     */
    private function formatPayloadAsPhpArray(?string $payload): string
    {
        if (!$payload) {
            return 'null';
        }

        // Try to decode JSON
        $decoded = json_decode($payload, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return var_export($decoded, true);
        }

        // If not valid JSON, treat as string
        return var_export($payload, true);
    }

    /**
     * Format payload data as formatted JSON
     */
    private function formatPayloadAsJson(?string $payload): string
    {
        if (!$payload) {
            return 'null';
        }

        // Try to decode and re-encode for pretty formatting
        $decoded = json_decode($payload, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Truncate upload_receipt field before displaying
            $decoded = $this->truncateUploadReceipt($decoded);
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // If not valid JSON, wrap in quotes and return as JSON string
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Truncate upload_receipt and captcha fields to 50 characters with ellipsis
     */
    private function truncateUploadReceipt(array $data): array
    {
        // Truncate upload_receipt field
        if (isset($data['upload_receipt']) && is_string($data['upload_receipt'])) {
            if (strlen($data['upload_receipt']) > 50) {
                $data['upload_receipt'] = substr($data['upload_receipt'], 0, 50) . '...';
            }
        }

        // Truncate captcha field
        if (isset($data['captcha']) && is_string($data['captcha'])) {
            if (strlen($data['captcha']) > 50) {
                $data['captcha'] = substr($data['captcha'], 0, 50) . '...';
            }
        }

        // Handle nested arrays recursively
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->truncateUploadReceipt($value);
            }
        }

        return $data;
    }
}