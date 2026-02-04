<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-sample-submission-data',
    description: 'Generate sample CSV file for testing submission data import'
)]
class GenerateSampleSubmissionDataCommand extends Command
{
    private array $firstNames = [
        'Ahmad', 'Ali', 'Aminah', 'Fatimah', 'Hassan', 'Ibrahim', 'Khadijah', 'Muhammad', 'Noor', 'Omar',
        'John', 'Jane', 'Michael', 'Sarah', 'David', 'Lisa', 'Robert', 'Emily', 'James', 'Jessica',
        'Wei Ming', 'Li Hua', 'Xiao Yu', 'Mei Lin', 'Chen Wei', 'Zhang Lei', 'Wang Fang', 'Liu Yang',
        'Raj', 'Priya', 'Arjun', 'Kavitha', 'Suresh', 'Deepa', 'Vikram', 'Anita', 'Ravi', 'Sita'
    ];

    private array $lastNames = [
        'Abdullah', 'Rahman', 'Hassan', 'Ahmad', 'Ibrahim', 'Ismail', 'Omar', 'Ali', 'Yusof', 'Mahmud',
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez',
        'Tan', 'Lim', 'Lee', 'Wong', 'Ng', 'Ong', 'Teo', 'Goh', 'Koh', 'Sim',
        'Kumar', 'Sharma', 'Patel', 'Singh', 'Gupta', 'Agarwal', 'Jain', 'Bansal', 'Mittal', 'Chopra'
    ];

    private array $cities = [
        'Kuala Lumpur', 'George Town', 'Ipoh', 'Shah Alam', 'Petaling Jaya', 'Klang', 'Johor Bahru',
        'Subang Jaya', 'Kuching', 'Kota Kinabalu', 'Seremban', 'Kuantan', 'Kota Bharu', 'Alor Setar',
        'Malacca City', 'Taiping', 'Sandakan', 'Miri', 'Sibu', 'Tawau'
    ];

    private array $states = [
        'Selangor', 'Johor', 'Sabah', 'Sarawak', 'Perak', 'Penang', 'Pahang', 'Kedah', 'Kelantan',
        'Terengganu', 'Malacca', 'Negeri Sembilan', 'Perlis', 'Kuala Lumpur', 'Putrajaya', 'Labuan'
    ];

    private array $submitTypes = [
        'CONTEST', 'PROMOTION', 'SURVEY', 'REGISTRATION', 'FEEDBACK', 'NEWSLETTER', 'EVENT'
    ];

    protected function configure(): void
    {
        $this
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of records to generate', 100)
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path', 'generated_submission_data.csv')
            ->addOption('include-header', null, InputOption::VALUE_NONE, 'Include header row in output')
            ->addOption('duplicate-users', null, InputOption::VALUE_OPTIONAL, 'Percentage of duplicate users (0-50)', 10)
            ->setHelp('
This command generates sample submission data for testing the import functionality.

Features:
- Generates realistic Malaysian names and data
- Creates valid mobile numbers and emails
- Includes address information
- Allows controlled duplicate users for testing
- Outputs in CSV format ready for import

Usage:
  php bin/console app:generate-sample-submission-data --count=500 --include-header
  php bin/console app:generate-sample-submission-data --output=test_data.csv --duplicate-users=20
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = (int) $input->getOption('count');
        $outputFile = $input->getOption('output');
        $includeHeader = $input->getOption('include-header');
        $duplicatePercentage = min(50, max(0, (int) $input->getOption('duplicate-users')));

        $io->title('Generate Sample Submission Data');

        if ($count <= 0) {
            $io->error('Count must be greater than 0');
            return Command::FAILURE;
        }

        $io->info("Generating {$count} records with {$duplicatePercentage}% duplicate users");

        try {
            $data = $this->generateData($count, $duplicatePercentage);
            $this->writeToFile($data, $outputFile, $includeHeader);

            $io->success("Generated {$count} records successfully!");
            $io->info("Output file: {$outputFile}");
            
            if ($duplicatePercentage > 0) {
                $duplicateCount = intval($count * $duplicatePercentage / 100);
                $io->note("Approximately {$duplicateCount} records will have duplicate users (same mobile number)");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error generating data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function generateData(int $count, int $duplicatePercentage): array
    {
        $data = [];
        $existingUsers = [];
        $duplicateCount = intval($count * $duplicatePercentage / 100);

        for ($i = 0; $i < $count; $i++) {
            // Decide if this should be a duplicate user
            $shouldDuplicate = $i < $duplicateCount && !empty($existingUsers) && rand(1, 100) <= 50;

            if ($shouldDuplicate) {
                // Use existing user data
                $existingUser = $existingUsers[array_rand($existingUsers)];
                $userData = $existingUser;
            } else {
                // Generate new user data
                $userData = $this->generateUserData();
                $existingUsers[] = $userData;
            }

            // Always generate new submission data
            $submissionData = $this->generateSubmissionData($i + 1);

            // Combine user and submission data
            $data[] = array_merge($userData, $submissionData);
        }

        return $data;
    }

    private function generateUserData(): array
    {
        $firstName = $this->firstNames[array_rand($this->firstNames)];
        $lastName = $this->lastNames[array_rand($this->lastNames)];
        $fullName = $firstName . ' ' . $lastName;

        // Generate Malaysian mobile number
        $mobilePrefix = ['012', '013', '014', '016', '017', '018', '019'];
        $prefix = $mobilePrefix[array_rand($mobilePrefix)];
        $mobileNo = '+60' . $prefix . rand(1000000, 9999999);

        // Generate email
        $emailPrefix = strtolower(str_replace(' ', '.', $fullName));
        $emailDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'example.com'];
        $email = $emailPrefix . rand(1, 999) . '@' . $emailDomains[array_rand($emailDomains)];

        // Generate national ID (Malaysian IC format)
        $year = rand(70, 99);
        $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $place = str_pad(rand(1, 59), 2, '0', STR_PAD_LEFT);
        $serial = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $gender_digit = rand(0, 9);
        $nationalId = $year . $month . $day . $place . $serial . $gender_digit;

        $gender = ($gender_digit % 2 == 1) ? 'M' : 'F';

        return [
            $fullName,      // full_name
            $mobileNo,      // mobile_no
            $email,         // email
            $nationalId,    // national_id
            $gender         // gender
        ];
    }

    private function generateSubmissionData(int $index): array
    {
        $submitCode = 'SUB' . str_pad($index, 6, '0', STR_PAD_LEFT);
        $submitType = $this->submitTypes[array_rand($this->submitTypes)];

        // Generate address
        $streetNumber = rand(1, 999);
        $streetNames = ['Jalan Bukit Bintang', 'Jalan Raja Chulan', 'Jalan Ampang', 'Jalan Tun Razak', 'Jalan Sultan Ismail'];
        $address1 = $streetNumber . ', ' . $streetNames[array_rand($streetNames)];
        
        $address2 = '';
        if (rand(1, 3) == 1) { // 33% chance of having address2
            $unitTypes = ['Apartment', 'Condominium', 'Flat', 'Unit'];
            $address2 = $unitTypes[array_rand($unitTypes)] . ' ' . rand(1, 50) . '-' . rand(1, 20);
        }

        $postcode = str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
        $city = $this->cities[array_rand($this->cities)];
        $state = $this->states[array_rand($this->states)];

        // Generate custom fields
        $customFields = [
            'Channel-' . rand(1, 5),
            'Source-' . chr(65 + rand(0, 25)),
            'Campaign-' . date('Y') . '-' . rand(1, 12),
            'Product-' . rand(100, 999),
            'Category-' . ['Electronics', 'Fashion', 'Food', 'Beauty', 'Sports'][array_rand(['Electronics', 'Fashion', 'Food', 'Beauty', 'Sports'])]
        ];

        return [
            $submitCode,        // submit_code
            $submitType,        // submit_type
            $address1,          // address_1
            $address2,          // address_2
            $postcode,          // postcode
            $city,              // city
            $state,             // state
            $customFields[0],   // field1
            $customFields[1],   // field2
            $customFields[2],   // field3
            $customFields[3],   // field4
            $customFields[4]    // field5
        ];
    }

    private function writeToFile(array $data, string $filename, bool $includeHeader): void
    {
        $file = fopen($filename, 'w');
        
        if (!$file) {
            throw new \RuntimeException("Cannot create file: {$filename}");
        }

        // Write header if requested
        if ($includeHeader) {
            $headers = [
                'full_name', 'mobile_no', 'email', 'national_id', 'gender',
                'submit_code', 'submit_type', 'address_1', 'address_2', 'postcode',
                'city', 'state', 'field1', 'field2', 'field3', 'field4', 'field5'
            ];
            fputcsv($file, $headers);
        }

        // Write data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);
    }
}