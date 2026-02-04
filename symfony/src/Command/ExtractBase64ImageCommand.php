<?php

namespace App\Command;

use App\Entity\Payload;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:extract-base64-image',
    description: 'Extract base64 image data from payload and optionally save to file'
)]
class ExtractBase64ImageCommand extends Command
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'Payload ID to retrieve')
            ->addArgument('field', InputArgument::OPTIONAL, 'Field name containing base64 image (e.g., upload_receipt, captcha)', 'upload_receipt')
            ->addOption('save', 's', InputOption::VALUE_OPTIONAL, 'Save image to file (provide filename or leave empty for auto-generated name)', false)
            ->addOption('output-dir', 'd', InputOption::VALUE_OPTIONAL, 'Output directory for saved images', 'var/images')
            ->addOption('display', null, InputOption::VALUE_NONE, 'Display the full base64 string in console')
            ->addOption('info', 'i', InputOption::VALUE_NONE, 'Display image information (size, type, etc.)')
            ->setHelp(<<<'HELP'
This command extracts base64 image data from a payload.

Usage examples:
  # Extract upload_receipt from payload ID 123
  php bin/console app:extract-base64-image 123

  # Extract captcha field from payload ID 123
  php bin/console app:extract-base64-image 123 captcha

  # Extract and save to file with auto-generated name
  php bin/console app:extract-base64-image 123 --save

  # Extract and save to specific filename
  php bin/console app:extract-base64-image 123 --save=receipt.jpg

  # Extract and save to custom directory
  php bin/console app:extract-base64-image 123 --save --output-dir=public/uploads

  # Display full base64 string
  php bin/console app:extract-base64-image 123 --display

  # Show image information
  php bin/console app:extract-base64-image 123 --info
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $payloadId = $input->getArgument('id');
        $fieldName = $input->getArgument('field');
        $saveOption = $input->getOption('save');
        $outputDir = $input->getOption('output-dir');
        $displayOption = $input->getOption('display');
        $infoOption = $input->getOption('info');

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

        $io->title(sprintf('Extracting Base64 Image from Payload (ID: %s)', $payloadId));

        // Decode payload JSON
        $payloadData = json_decode($payload->getPayload(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Failed to decode payload JSON: ' . json_last_error_msg());
            return Command::FAILURE;
        }

        // Extract base64 image data
        $base64Data = $this->extractBase64FromField($payloadData, $fieldName);

        if (!$base64Data) {
            $io->error(sprintf('Field "%s" not found or empty in payload.', $fieldName));
            $io->note('Available fields: ' . implode(', ', array_keys($payloadData)));
            return Command::FAILURE;
        }

        // Clean base64 data (remove data URI prefix if present)
        $cleanBase64 = $this->cleanBase64Data($base64Data);
        
        // Get image information
        $imageInfo = $this->getImageInfo($cleanBase64);

        // Display basic information
        $io->section('Extraction Results');
        $io->writeln(sprintf('Field: <info>%s</info>', $fieldName));
        $io->writeln(sprintf('Base64 Length: <info>%s</info> characters', number_format(strlen($cleanBase64))));
        
        if ($imageInfo) {
            $io->writeln(sprintf('Image Type: <info>%s</info>', $imageInfo['mime_type']));
            $io->writeln(sprintf('Image Size: <info>%s</info> bytes', number_format($imageInfo['size'])));
            if (isset($imageInfo['width']) && isset($imageInfo['height'])) {
                $io->writeln(sprintf('Dimensions: <info>%dx%d</info> pixels', $imageInfo['width'], $imageInfo['height']));
            }
        }

        // Display full base64 string if requested
        if ($displayOption) {
            $io->section('Base64 Data');
            $io->writeln($base64Data);
        }

        // Display detailed image information if requested
        if ($infoOption && $imageInfo) {
            $io->section('Detailed Image Information');
            $io->table(
                ['Property', 'Value'],
                [
                    ['MIME Type', $imageInfo['mime_type']],
                    ['File Size', number_format($imageInfo['size']) . ' bytes'],
                    ['Width', $imageInfo['width'] ?? 'N/A'],
                    ['Height', $imageInfo['height'] ?? 'N/A'],
                    ['Has Data URI Prefix', $imageInfo['has_prefix'] ? 'Yes' : 'No'],
                    ['Data URI Prefix', $imageInfo['prefix'] ?? 'N/A'],
                ]
            );
        }

        // Save to file if requested
        if ($saveOption !== false) {
            $filename = $this->saveBase64ToFile(
                $cleanBase64,
                $saveOption ?: null,
                $outputDir,
                $fieldName,
                $payloadId,
                $imageInfo
            );

            if ($filename) {
                $io->success(sprintf('Image saved to: %s', $filename));
            } else {
                $io->error('Failed to save image to file.');
                return Command::FAILURE;
            }
        }

        $io->success('Base64 image data extracted successfully.');
        
        return Command::SUCCESS;
    }

    /**
     * Extract base64 data from a specific field in the payload
     */
    private function extractBase64FromField(array $data, string $fieldName): ?string
    {
        // Direct field access
        if (isset($data[$fieldName])) {
            return $data[$fieldName];
        }

        // Try nested search
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result = $this->extractBase64FromField($value, $fieldName);
                if ($result) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Clean base64 data by removing data URI prefix if present
     */
    private function cleanBase64Data(string $base64Data): string
    {
        // Remove data URI prefix (e.g., "data:image/jpeg;base64,")
        if (preg_match('/^data:([^;]+);base64,(.+)$/', $base64Data, $matches)) {
            return $matches[2];
        }

        return $base64Data;
    }

    /**
     * Get information about the base64 encoded image
     */
    private function getImageInfo(string $cleanBase64): ?array
    {
        try {
            $imageData = base64_decode($cleanBase64, true);
            
            if ($imageData === false) {
                return null;
            }

            $info = [
                'size' => strlen($imageData),
                'has_prefix' => false,
                'prefix' => null,
            ];

            // Detect MIME type from binary data
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
            $info['mime_type'] = $mimeType;

            // Try to get image dimensions
            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            file_put_contents($tempFile, $imageData);
            
            $imageSize = @getimagesize($tempFile);
            if ($imageSize !== false) {
                $info['width'] = $imageSize[0];
                $info['height'] = $imageSize[1];
            }
            
            unlink($tempFile);

            return $info;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Save base64 data to a file
     */
    private function saveBase64ToFile(
        string $cleanBase64,
        ?string $filename,
        string $outputDir,
        string $fieldName,
        int $payloadId,
        ?array $imageInfo
    ): ?string {
        try {
            // Decode base64
            $imageData = base64_decode($cleanBase64, true);
            
            if ($imageData === false) {
                return null;
            }

            // Create output directory if it doesn't exist
            $fullOutputDir = getcwd() . '/' . $outputDir;
            if (!is_dir($fullOutputDir)) {
                mkdir($fullOutputDir, 0755, true);
            }

            // Generate filename if not provided
            if (!$filename) {
                $extension = $this->getExtensionFromMimeType($imageInfo['mime_type'] ?? 'image/jpeg');
                $filename = sprintf(
                    'payload_%d_%s_%s.%s',
                    $payloadId,
                    $fieldName,
                    date('Y-m-d_H-i-s'),
                    $extension
                );
            }

            // Full path
            $fullPath = $fullOutputDir . '/' . $filename;

            // Save file
            $result = file_put_contents($fullPath, $imageData);

            return $result !== false ? $fullPath : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get file extension from MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/svg+xml' => 'svg',
        ];

        return $mimeMap[$mimeType] ?? 'jpg';
    }
}