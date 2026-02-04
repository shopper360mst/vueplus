<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Submission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[AsCommand(
    name: 'app:import-submission-data',
    description: 'Import submission data from CSV/Excel files. Creates users if they don\'t exist based on mobile number.'
)]
class ImportSubmissionDataCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV/Excel file')
            ->addOption('batch-size', 'b', InputOption::VALUE_OPTIONAL, 'Batch size for processing', 100)
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Perform a dry run without saving to database')
            ->addOption('skip-header', 's', InputOption::VALUE_NONE, 'Skip the first row (header row)')
            ->setHelp('
This command imports submission data from CSV or Excel files.

Expected CSV/Excel columns (in order):
1. full_name - Full name of the user
2. mobile_no - Mobile number (unique identifier)
3. email - Email address
4. national_id - National ID (optional)
5. gender - Gender (M/F or Male/Female)
6. submit_code - Submission code
7. submit_type - Type of submission (optional)
8. address_1 - Address line 1 (optional)
9. address_2 - Address line 2 (optional)
10. postcode - Postal code (optional)
11. city - City (optional)
12. state - State (optional)
13. field1 - Custom field 1 (optional)
14. field2 - Custom field 2 (optional)
15. field3 - Custom field 3 (optional)
16. field4 - Custom field 4 (optional)
17. field5 - Custom field 5 (optional)

Usage:
  php bin/console app:import-submission-data /path/to/file.csv
  php bin/console app:import-submission-data /path/to/file.xlsx --batch-size=50 --skip-header
  php bin/console app:import-submission-data /path/to/file.csv --dry-run
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $batchSize = (int) $input->getOption('batch-size');
        $isDryRun = $input->getOption('dry-run');
        $skipHeader = $input->getOption('skip-header');

        // Validate file exists
        if (!file_exists($filePath)) {
            $io->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $io->title('Import Submission Data Command');
        
        if ($isDryRun) {
            $io->note('DRY RUN MODE - No data will be saved to database');
        }

        try {
            $data = $this->readFile($filePath);
            
            if (empty($data)) {
                $io->error('No data found in file or file is empty');
                return Command::FAILURE;
            }

            $io->info("Found " . count($data) . " rows in file");

            // Skip header if requested
            if ($skipHeader && count($data) > 0) {
                array_shift($data);
                $io->info("Skipped header row. Processing " . count($data) . " data rows");
            }

            $stats = $this->processData($data, $batchSize, $isDryRun, $io);

            $this->displayResults($stats, $io);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error processing file: ' . $e->getMessage());
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
        if (!class_exists('Box\Spout\Reader\Common\Creator\ReaderEntityFactory')) {
            throw new \RuntimeException('Box Spout library not found. Please install box/spout');
        }

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
        
        // Convert associative array to indexed array if needed
        if (is_array($data) && !empty($data) && is_array($data[0])) {
            return array_map('array_values', $data);
        }
        
        return $data;
    }

    private function processData(array $data, int $batchSize, bool $isDryRun, SymfonyStyle $io): array
    {
        $stats = [
            'total_rows' => count($data),
            'users_created' => 0,
            'users_found' => 0,
            'submissions_created' => 0,
            'errors' => 0,
            'error_details' => []
        ];

        // We won't use a global transaction since we're flushing per user
        // This prevents duplicate username errors by committing each user immediately
        try {
            $progressBar = $io->createProgressBar($stats['total_rows']);
            $progressBar->start();

            foreach ($data as $rowIndex => $row) {
                // Start transaction for each row to allow individual commits/rollbacks
                if (!$isDryRun) {
                    $this->entityManager->getConnection()->beginTransaction();
                }
                
                try {
                    $result = $this->processRow($row, $rowIndex + 1, $isDryRun);
                    
                    if ($result['user_created']) {
                        $stats['users_created']++;
                    } else {
                        $stats['users_found']++;
                    }
                    
                    if ($result['submission_created']) {
                        $stats['submissions_created']++;
                    }

                    // Commit the transaction for this row
                    if (!$isDryRun) {
                        // createUser already flushes the user entity
                        // Flush any remaining entities for this row
                        $this->entityManager->flush();
                        $this->entityManager->getConnection()->commit();
                    }
                    
                    // Clear entity manager periodically to free memory
                    if (!$isDryRun && (($rowIndex + 1) % $batchSize) === 0) {
                        $this->entityManager->clear();
                    }

                } catch (\Exception $e) {
                    // Roll back transaction for this row
                    if (!$isDryRun) {
                        $this->entityManager->getConnection()->rollBack();
                    }
                    
                    $stats['errors']++;
                    $stats['error_details'][] = "Row " . ($rowIndex + 1) . ": " . $e->getMessage();
                    
                    if ($stats['errors'] > 10) {
                        $stats['error_details'][] = "... and more errors (showing first 10)";
                        break;
                    }
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);

        } catch (\Exception $e) {
            // Handle any exceptions not caught in the row processing
            throw $e;
        }

        return $stats;
    }

    private function processRow(array $row, int $rowNumber, bool $isDryRun): array
    {
        // Validate minimum required fields
        if (count($row) < 6) {
            throw new \InvalidArgumentException("Row {$rowNumber}: Insufficient columns. Expected at least 6 columns (full_name, mobile_no, email, national_id, gender, submit_code)");
        }

        // Extract data from row
        $userData = $this->extractUserData($row);
        $submissionData = $this->extractSubmissionData($row);

        // Validate required fields
        $this->validateRequiredFields($userData, $submissionData, $rowNumber);

        // Check if user exists by mobile number first
        $user = $this->findUserByMobileNumber($userData['mobile_no']);
        $userCreated = false;

        if (!$user) {
            // Generate potential username for the new user
            $potentialUsername = $this->generateUsername($userData['email'], $userData['mobile_no']);
            
            // Check if user exists by email or username
            $userByEmail = !empty($userData['email']) ? $this->findUserByEmail($userData['email']) : null;
            $userByUsername = $this->findUserByUsername($potentialUsername);
            
            if ($userByEmail) {
                // Use existing user with the same email
                $user = $userByEmail;
                throw new \InvalidArgumentException("Row {$rowNumber}: User with email '{$userData['email']}' already exists. Skipping user creation.");
            } else if ($userByUsername) {
                // Use existing user with the same username
                $user = $userByUsername;
                throw new \InvalidArgumentException("Row {$rowNumber}: User with username '{$potentialUsername}' already exists. Skipping user creation.");
            } else {
                // Create new user only if no existing user found by mobile, email, or username
                $user = $this->createUser($userData, $isDryRun);
                $userCreated = true;
            }
        }
        
        // If we don't have a user at this point, we can't create a submission
        if (!$user) {
            throw new \InvalidArgumentException("Row {$rowNumber}: Unable to create or find a user for this submission.");
        }

        // Create submission
        $submission = $this->createSubmission($submissionData, $user, $isDryRun);

        return [
            'user_created' => $userCreated,
            'submission_created' => true
        ];
    }

    private function extractUserData(array $row): array
    {
        $mobileNo = trim($row[1] ?? '');
        $email = trim($row[2] ?? '');
        
        // If email is empty, generate a dummy email using the mobile number
        if (empty($email) && !empty($mobileNo)) {
            // Clean mobile number for use in email (remove non-alphanumeric chars)
            $cleanMobile = preg_replace('/[^0-9]/', '', $mobileNo);
            $email = "user_{$cleanMobile}@dummy-import.com";
        }
        
        return [
            'full_name' => trim($row[0] ?? ''),
            'mobile_no' => $mobileNo,
            'email' => $email,
            'national_id' => trim($row[3] ?? ''),
            'gender' => $this->normalizeGender(trim($row[4] ?? '')),
        ];
    }

    private function extractSubmissionData(array $row): array
    {
        return [
            'submit_code' => trim($row[5] ?? ''),
            'submit_type' => trim($row[6] ?? ''),
            'address_1' => trim($row[7] ?? ''),
            'address_2' => trim($row[8] ?? ''),
            'postcode' => trim($row[9] ?? ''),
            'city' => trim($row[10] ?? ''),
            'state' => trim($row[11] ?? ''),
            'field1' => trim($row[12] ?? ''),
            'field2' => trim($row[13] ?? ''),
            'field3' => trim($row[14] ?? ''),
            'field4' => trim($row[15] ?? ''),
            'field5' => trim($row[16] ?? ''),
        ];
    }

    private function validateRequiredFields(array $userData, array $submissionData, int $rowNumber): void
    {
        if (empty($userData['full_name'])) {
            throw new \InvalidArgumentException("Row {$rowNumber}: Full name is required");
        }

        if (empty($userData['mobile_no'])) {
            throw new \InvalidArgumentException("Row {$rowNumber}: Mobile number is required");
        }

        if (empty($submissionData['submit_code'])) {
            throw new \InvalidArgumentException("Row {$rowNumber}: Submit code is required");
        }

        // Validate email format (should always be valid since we generate a dummy email if none provided)
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Row {$rowNumber}: Invalid email format");
        }

        // Validate mobile number format (basic validation)
        if (!preg_match('/^[0-9+\-\s()]+$/', $userData['mobile_no'])) {
            throw new \InvalidArgumentException("Row {$rowNumber}: Invalid mobile number format");
        }
    }

    private function normalizeGender(string $gender): string
    {
        $gender = strtolower($gender);
        
        if (in_array($gender, ['m', 'male', '1'])) {
            return 'M';
        } elseif (in_array($gender, ['f', 'female', '0'])) {
            return 'F';
        }
        
        return 'M'; // Default to M if not specified
    }

    private function findUserByMobileNumber(string $mobileNo): ?User
    {
        // Since mobile numbers are encrypted, we need to find by encrypted value
        // We'll need to encrypt the input mobile number to compare
        $tempUser = new User();
        $encryptedMobile = $tempUser->encrypt($mobileNo);
        
        return $this->entityManager->getRepository(User::class)
            ->findOneBy(['mobile_no' => $encryptedMobile]);
    }
    
    private function findUserByUsername(string $username): ?User
    {
        return $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => $username]);
    }
    
    private function findUserByEmail(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);
    }

    private function createUser(array $userData, bool $isDryRun): User
    {
        $user = new User();
        
        // Generate username from email or mobile
        $username = $this->generateUsername($userData['email'], $userData['mobile_no']);
        
        $user->setUsername($username);
        $user->setFullName($userData['full_name']);
        $user->setMobileNo($userData['mobile_no']);
        $user->setEmail($userData['email']);
        
        if (!empty($userData['national_id'])) {
            $user->setNationalId($userData['national_id']);
        }
        
        $user->setGender($userData['gender']);
        $user->setRoles(['ROLE_USER']);
        
        // Set default password (should be changed by user)
        $defaultPassword = 'password123'; // You might want to generate a random password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $defaultPassword);
        $user->setPassword($hashedPassword);
        
        // Set default values
        $user->setActive(true);
        $user->setGuest(false);
        $user->setVisible(true);
        $user->setDeleted(false);
        $user->setSubmissionCount(0);
        $user->setCreatedDate(new \DateTime());
        $user->setUpdatedDate(new \DateTime());
        
        if (!$isDryRun) {
            $this->entityManager->persist($user);
            // Immediately flush to ensure username is in database
            // This prevents duplicate username errors in batch processing
            $this->entityManager->flush($user);
        }
        
        return $user;
    }

    private function createSubmission(array $submissionData, User $user, bool $isDryRun): Submission
    {
        $submission = new Submission();
        
        $submission->setSubmitCode($submissionData['submit_code']);
        
        if (!empty($submissionData['submit_type'])) {
            $submission->setSubmitType($submissionData['submit_type']);
        }
        
        // Copy user data to submission
        $submission->setFullName($user->getFullName());
        $submission->setMobileNo($user->getMobileNo());
        $submission->setEmail($user->getEmail());
        $submission->setNationalId($user->getNationalId());
        $submission->setGender($user->getGender());
        
        // Set address data
        if (!empty($submissionData['address_1'])) {
            $submission->setAddress1($submissionData['address_1']);
        }
        if (!empty($submissionData['address_2'])) {
            $submission->setAddress2($submissionData['address_2']);
        }
        if (!empty($submissionData['postcode'])) {
            $submission->setPostcode($submissionData['postcode']);
        }
        if (!empty($submissionData['city'])) {
            $submission->setCity($submissionData['city']);
        }
        if (!empty($submissionData['state'])) {
            $submission->setState($submissionData['state']);
        }
        
        // Set custom fields
        if (!empty($submissionData['field1'])) {
            $submission->setField1($submissionData['field1']);
        }
        if (!empty($submissionData['field2'])) {
            $submission->setField2($submissionData['field2']);
        }
        if (!empty($submissionData['field3'])) {
            $submission->setField3($submissionData['field3']);
        }
        if (!empty($submissionData['field4'])) {
            $submission->setField4($submissionData['field4']);
        }
        if (!empty($submissionData['field5'])) {
            $submission->setField5($submissionData['field5']);
        }
        
        $submission->setStatus('APPROVED'); // Default status
        $submission->setCreatedDate(new \DateTime());
        $submission->setUser($user);
        
        if (!$isDryRun) {
            $this->entityManager->persist($submission);
            
            // Update user submission count
            $currentCount = $user->getSubmissionCount() ?? 0;
            $user->setSubmissionCount($currentCount + 1);
            $user->setUpdatedDate(new \DateTime());
        }
        
        return $submission;
    }

    private function generateUsername(string $email, string $mobileNo): string
    {
        // Try to use email prefix first
        $emailPrefix = strstr($email, '@', true);
        if ($emailPrefix && strlen($emailPrefix) >= 3) {
            $username = $emailPrefix;
        } else {
            // Use mobile number as fallback
            $username = 'user_' . preg_replace('/[^0-9]/', '', $mobileNo);
        }
        
        // Check if username exists and make it unique
        $originalUsername = $username;
        $counter = 1;
        
        while ($this->usernameExists($username)) {
            $username = $originalUsername . '_' . $counter;
            $counter++;
        }
        
        return $username;
    }

    private function usernameExists(string $username): bool
    {
        return $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => $username]) !== null;
    }

    private function displayResults(array $stats, SymfonyStyle $io): void
    {
        $io->success('Import completed!');
        
        $io->table(
            ['Metric', 'Count'],
            [
                ['Total rows processed', $stats['total_rows']],
                ['New users created', $stats['users_created']],
                ['Existing users found', $stats['users_found']],
                ['Submissions created', $stats['submissions_created']],
                ['Errors encountered', $stats['errors']],
            ]
        );

        if (!empty($stats['error_details'])) {
            $io->warning('Errors encountered:');
            foreach ($stats['error_details'] as $error) {
                $io->text('• ' . $error);
            }
        }

        if ($stats['errors'] === 0) {
            $io->note('All rows processed successfully!');
        } elseif ($stats['errors'] < $stats['total_rows']) {
            $io->note('Import completed with some errors. Please review the error details above.');
        } else {
            $io->error('Import failed. Please check your data format and try again.');
        }
    }
}