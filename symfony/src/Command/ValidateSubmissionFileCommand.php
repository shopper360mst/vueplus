<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[AsCommand(
    name: 'app:validate-submission-file',
    description: 'Validate CSV/Excel file format for submission data import'
)]
class ValidateSubmissionFileCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV/Excel file')
            ->addOption('skip-header', 's', InputOption::VALUE_NONE, 'Skip the first row (header row)')
            ->addOption('show-samples', null, InputOption::VALUE_OPTIONAL, 'Number of sample rows to display', 5)
            ->setHelp('
This command validates the format of CSV/Excel files before importing submission data.

It checks:
- File format and readability
- Column count and structure
- Required field validation
- Data format validation
- Duplicate detection

Usage:
  php bin/console app:validate-submission-file /path/to/file.csv
  php bin/console app:validate-submission-file /path/to/file.xlsx --skip-header --show-samples=10
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $skipHeader = $input->getOption('skip-header');
        $showSamples = (int) $input->getOption('show-samples');

        $io->title('Submission File Validation');

        // Check if file exists
        if (!file_exists($filePath)) {
            $io->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        try {
            // Read file
            $data = $this->readFile($filePath);
            
            if (empty($data)) {
                $io->error('File is empty or could not be read');
                return Command::FAILURE;
            }

            $io->success("File read successfully: " . count($data) . " rows found");

            // Skip header if requested
            $originalRowCount = count($data);
            if ($skipHeader && count($data) > 0) {
                $headerRow = array_shift($data);
                $io->info("Header row: " . implode(', ', array_slice($headerRow, 0, 10)) . (count($headerRow) > 10 ? '...' : ''));
                $io->info("Skipped header row. Validating " . count($data) . " data rows");
            }

            // Validate structure
            $this->validateStructure($data, $io);

            // Validate data
            $validationResults = $this->validateData($data, $io);

            // Show samples
            if ($showSamples > 0 && count($data) > 0) {
                $this->showSampleData($data, $showSamples, $io);
            }

            // Show summary
            $this->showSummary($validationResults, $originalRowCount, $io);

            return $validationResults['has_errors'] ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error reading file: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function readFile(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->readExcelFile($filePath);
        } elseif ($extension === 'csv') {
            return $this->readCsvFile($filePath);
        } else {
            throw new \InvalidArgumentException("Unsupported file format: {$extension}. Supported formats: csv, xlsx, xls");
        }
    }

    private function readExcelFile(string $filePath): array
    {
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($filePath);
        
        $data = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            // Only read the first sheet
            foreach ($sheet->getRowIterator() as $row) {
                $rowData = [];
                foreach ($row->getCells() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $data[] = $rowData;
            }
            break; // Only process first sheet
        }
        
        $reader->close();
        return $data;
    }

    private function readCsvFile(string $filePath): array
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        $data = $serializer->decode(file_get_contents($filePath), CsvEncoder::FORMAT);
        
        if (is_array($data) && !empty($data) && is_array($data[0])) {
            return array_map('array_values', $data);
        }
        
        return $data;
    }

    private function validateStructure(array $data, SymfonyStyle $io): void
    {
        $io->section('Structure Validation');

        if (empty($data)) {
            $io->error('No data rows found');
            return;
        }

        // Check column count
        $expectedMinColumns = 6; // minimum required columns
        $firstRowColumns = count($data[0]);
        
        if ($firstRowColumns < $expectedMinColumns) {
            $io->error("Insufficient columns. Found {$firstRowColumns}, expected at least {$expectedMinColumns}");
        } else {
            $io->success("Column count OK: {$firstRowColumns} columns found");
        }

        // Check consistency across rows
        $columnCounts = array_map('count', $data);
        $uniqueCounts = array_unique($columnCounts);
        
        if (count($uniqueCounts) > 1) {
            $io->warning('Inconsistent column counts across rows:');
            $countFreq = array_count_values($columnCounts);
            foreach ($countFreq as $count => $frequency) {
                $io->text("  {$count} columns: {$frequency} rows");
            }
        } else {
            $io->success('All rows have consistent column count');
        }
    }

    private function validateData(array $data, SymfonyStyle $io): array
    {
        $io->section('Data Validation');

        $results = [
            'total_rows' => count($data),
            'valid_rows' => 0,
            'errors' => [],
            'warnings' => [],
            'mobile_numbers' => [],
            'emails' => [],
            'submit_codes' => [],
            'has_errors' => false
        ];

        foreach ($data as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $rowErrors = [];
            $rowWarnings = [];

            // Validate required fields
            if (empty(trim($row[0] ?? ''))) {
                $rowErrors[] = "Missing full_name";
            }

            $mobileNo = trim($row[1] ?? '');
            if (empty($mobileNo)) {
                $rowErrors[] = "Missing mobile_no";
            } else {
                if (!preg_match('/^[0-9+\-\s()]+$/', $mobileNo)) {
                    $rowErrors[] = "Invalid mobile_no format";
                }
                $results['mobile_numbers'][] = $mobileNo;
            }

            $email = trim($row[2] ?? '');
            if (empty($email)) {
                $rowErrors[] = "Missing email";
            } else {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "Invalid email format";
                }
                $results['emails'][] = $email;
            }

            $submitCode = trim($row[5] ?? '');
            if (empty($submitCode)) {
                $rowErrors[] = "Missing submit_code";
            } else {
                $results['submit_codes'][] = $submitCode;
            }

            // Validate gender
            $gender = strtolower(trim($row[4] ?? ''));
            if (!empty($gender) && !in_array($gender, ['m', 'f', 'male', 'female', '1', '0'])) {
                $rowWarnings[] = "Unknown gender format: '{$gender}' (will default to M)";
            }

            // Store errors and warnings
            if (!empty($rowErrors)) {
                $results['errors'][$rowNumber] = $rowErrors;
                $results['has_errors'] = true;
            } else {
                $results['valid_rows']++;
            }

            if (!empty($rowWarnings)) {
                $results['warnings'][$rowNumber] = $rowWarnings;
            }
        }

        // Check for duplicates
        $this->checkDuplicates($results, $io);

        return $results;
    }

    private function checkDuplicates(array &$results, SymfonyStyle $io): void
    {
        // Check duplicate mobile numbers
        $mobileDuplicates = array_filter(array_count_values($results['mobile_numbers']), fn($count) => $count > 1);
        if (!empty($mobileDuplicates)) {
            $io->warning('Duplicate mobile numbers found:');
            foreach ($mobileDuplicates as $mobile => $count) {
                $io->text("  {$mobile}: {$count} occurrences");
            }
        }

        // Check duplicate emails
        $emailDuplicates = array_filter(array_count_values($results['emails']), fn($count) => $count > 1);
        if (!empty($emailDuplicates)) {
            $io->warning('Duplicate emails found:');
            foreach ($emailDuplicates as $email => $count) {
                $io->text("  {$email}: {$count} occurrences");
            }
        }

        // Check duplicate submit codes
        $codeDuplicates = array_filter(array_count_values($results['submit_codes']), fn($count) => $count > 1);
        if (!empty($codeDuplicates)) {
            $io->warning('Duplicate submit codes found:');
            foreach ($codeDuplicates as $code => $count) {
                $io->text("  {$code}: {$count} occurrences");
            }
        }
    }

    private function showSampleData(array $data, int $sampleCount, SymfonyStyle $io): void
    {
        $io->section('Sample Data');

        $headers = ['Row', 'Full Name', 'Mobile', 'Email', 'Gender', 'Submit Code', 'Type'];
        $sampleRows = [];

        $count = min($sampleCount, count($data));
        for ($i = 0; $i < $count; $i++) {
            $row = $data[$i];
            $sampleRows[] = [
                $i + 1,
                substr(trim($row[0] ?? ''), 0, 20),
                substr(trim($row[1] ?? ''), 0, 15),
                substr(trim($row[2] ?? ''), 0, 25),
                trim($row[4] ?? ''),
                trim($row[5] ?? ''),
                trim($row[6] ?? '')
            ];
        }

        $io->table($headers, $sampleRows);
    }

    private function showSummary(array $results, int $originalRowCount, SymfonyStyle $io): void
    {
        $io->section('Validation Summary');

        $io->table(
            ['Metric', 'Count'],
            [
                ['Total rows in file', $originalRowCount],
                ['Data rows validated', $results['total_rows']],
                ['Valid rows', $results['valid_rows']],
                ['Rows with errors', count($results['errors'])],
                ['Rows with warnings', count($results['warnings'])],
                ['Unique mobile numbers', count(array_unique($results['mobile_numbers']))],
                ['Unique emails', count(array_unique($results['emails']))],
                ['Unique submit codes', count(array_unique($results['submit_codes']))]
            ]
        );

        // Show errors
        if (!empty($results['errors'])) {
            $io->error('Validation Errors:');
            $errorCount = 0;
            foreach ($results['errors'] as $rowNumber => $errors) {
                if ($errorCount >= 10) {
                    $io->text('... and more errors (showing first 10)');
                    break;
                }
                $io->text("Row {$rowNumber}: " . implode(', ', $errors));
                $errorCount++;
            }
        }

        // Show warnings
        if (!empty($results['warnings'])) {
            $io->warning('Validation Warnings:');
            $warningCount = 0;
            foreach ($results['warnings'] as $rowNumber => $warnings) {
                if ($warningCount >= 10) {
                    $io->text('... and more warnings (showing first 10)');
                    break;
                }
                $io->text("Row {$rowNumber}: " . implode(', ', $warnings));
                $warningCount++;
            }
        }

        // Final status
        if ($results['has_errors']) {
            $io->error('File validation failed. Please fix the errors before importing.');
        } else {
            $io->success('File validation passed! Ready for import.');
            if (!empty($results['warnings'])) {
                $io->note('There are warnings that you may want to review.');
            }
        }
    }
}