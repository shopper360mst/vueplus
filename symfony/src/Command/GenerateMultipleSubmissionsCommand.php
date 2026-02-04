<?php

namespace App\Command;

use App\Entity\Submission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-multiple-submissions',
    description: 'Generate 4 test submissions: 3 GWP (MONT, SHM, TONT) and 1 CVSTOFT'
)]
class GenerateMultipleSubmissionsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserPasswordHasherInterface $passwordHasher;

    // Sample data arrays
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

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Generate Multiple Test Submissions');

        // Define the submissions to create
        $submissionsConfig = [
            ['submit_code' => 'GWP', 'submit_type' => 'MONT'],
            ['submit_code' => 'GWP', 'submit_type' => 'SHM'],
            ['submit_code' => 'GWP', 'submit_type' => 'TONT'],
            ['submit_code' => 'CVSTOFT', 'submit_type' => 'CVSTOFT'],
            // ['submit_code' => 'GWP', 'submit_type' => 'ECOMM'], // Adding 5th submission as requested
        ];

        $io->info('Creating test user and 4 submissions...');

        $this->entityManager->beginTransaction();

        try {
            // Create or get existing test user for all submissions
            $user = $this->createTestUser();
            
            // Only persist if it's a new user (doesn't have an ID yet)
            if ($user->getId() === null) {
                $this->entityManager->persist($user);
                $this->entityManager->flush(); // Flush to get the user ID
            }

            $createdSubmissions = [];

            foreach ($submissionsConfig as $index => $config) {
                $submission = $this->createSubmission($config, $index + 1, $user);
                
                // Validate the submission
                $violations = $this->validator->validate($submission);
                
                if (count($violations) > 0) {
                    $io->error("Validation failed for submission " . ($index + 1) . ":");
                    foreach ($violations as $violation) {
                        $io->writeln("  - {$violation->getPropertyPath()}: {$violation->getMessage()}");
                    }
                    $this->entityManager->rollback();
                    return Command::FAILURE;
                }

                $this->entityManager->persist($submission);
                $createdSubmissions[] = [
                    'index' => $index + 1,
                    'submit_code' => $config['submit_code'],
                    'submit_type' => $config['submit_type'],
                    'full_name' => $submission->getFullName(),
                    'mobile_no' => $submission->getMobileNo(),
                    'email' => $submission->getEmail(),
                    'field2' => $submission->getField2(),
                    'field3' => $submission->getField3(),
                    'field4' => $submission->getField4()
                ];
            }

            $this->entityManager->flush();
            $this->entityManager->commit();

            $io->success('✅ All 4 submissions created successfully!');

            // Display summary table
            $io->section('Created Submissions Summary:');
            $tableData = [];
            foreach ($createdSubmissions as $sub) {
                $tableData[] = [
                    $sub['index'],
                    $sub['submit_code'],
                    $sub['submit_type'],
                    $sub['full_name'],
                    $sub['mobile_no'],
                    $sub['email'],
                    $sub['field2'],
                    $sub['field3'],
                    $sub['field4']
                ];
            }

            $io->table(
                ['#', 'Submit Code', 'Submit Type', 'Full Name', 'Mobile No', 'Email', 'DIY ID', 'Age', 'Age Range'],
                $tableData
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $io->error('❌ Error creating submissions: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createSubmission(array $config, int $index, User $user): Submission
    {
        $submission = new Submission();

        // Use same user data for all submissions (index 1)
        $userData = $this->generateUserData(1);
        
        // Set basic submission data
        $submission->setSubmitCode($config['submit_code']);
        $submission->setSubmitType($config['submit_type']);
        $submission->setStatus('PROCESSING');
        $submission->setCreatedDate(new \DateTime());
        $submission->setUser($user);

        // Set user information (encrypted fields)
        $submission->setFullName($userData['full_name']);
        $submission->setMobileNo($userData['mobile_no']);
        $submission->setEmail($userData['email']);
        $submission->setNationalId($userData['national_id']);

        // Set receiver information (same as submitter for these test cases)
        $submission->setReceiverFullName($userData['full_name']);
        $submission->setReceiverMobileNo($userData['mobile_no']);

        // Set address information
        if ($config['submit_code'] !== 'CVSTOFT') {
            // Use same address data for all GWP submissions (index 1)
            $addressData = $this->generateAddressData(1);
            $submission->setAddress1($addressData['address_1']);
            if (!empty($addressData['address_2'])) {
                $submission->setAddress2($addressData['address_2']);
            }
            $submission->setPostcode($addressData['postcode']);
            $submission->setCity($addressData['city']);
            $submission->setState($addressData['state']);
        } else {
            // For CVSTOFT, set minimal address to pass validation
            // In production, you might want to modify the validation rules
            $submission->setAddress1('N/A - CVSTOFT');
            $submission->setPostcode('00000');
            $submission->setCity('N/A');
            $submission->setState('N/A');
        }

        // Set gender
        $submission->setGender($userData['gender']);

        // Set additional fields based on submit_type
        $this->setAdditionalFields($submission, $config, $index);

        return $submission;
    }

    private function generateUserData(int $index): array
    {
        // Use consistent data for index 1 (all submissions will use same person)
        if ($index == 1) {
            $fullName = 'Ahmad Rahman';
            $mobileNo = '60123456789';
            $email = 'ahmad.rahman@gmail.com';
            $nationalId = '850315-08-1234'; // Born 15 March 1985, age 39
            $gender = 'M'; // Odd last digit = Male
        } else {
            // Fallback for other indices (shouldn't be used in current implementation)
            $firstName = $this->firstNames[array_rand($this->firstNames)];
            $lastName = $this->lastNames[array_rand($this->lastNames)];
            $fullName = $firstName . ' ' . $lastName;

            // Generate Malaysian mobile number (format: 01X-XXXXXXX)
            $mobilePrefix = ['012', '013', '014', '016', '017', '018', '019'];
            $prefix = $mobilePrefix[array_rand($mobilePrefix)];
            $suffix = str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
            $mobileNo = $prefix . '-' . $suffix;

            // Generate email
            $emailPrefix = strtolower(str_replace(' ', '.', $fullName));
            $emailDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
            $email = $emailPrefix . $index . '@' . $emailDomains[array_rand($emailDomains)];

            // Generate national ID (Malaysian IC format) - ensuring age > 21
            $currentYear = (int)date('Y');
            $birthYear = $currentYear - rand(25, 60); // Age between 25-60
            $year = str_pad($birthYear % 100, 2, '0', STR_PAD_LEFT);
            $month = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
            $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
            $place = str_pad(rand(1, 59), 2, '0', STR_PAD_LEFT);
            $serial = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $genderDigit = rand(0, 9);
            $nationalId = $year . $month . $day . '-' . $place . '-' . $serial . $genderDigit;

            $gender = ($genderDigit % 2 == 1) ? 'M' : 'F';
        }

        return [
            'full_name' => $fullName,
            'mobile_no' => $mobileNo,
            'email' => $email,
            'national_id' => $nationalId,
            'gender' => $gender
        ];
    }

    private function generateAddressData(int $index = 1): array
    {
        // Use index to ensure consistent address for same index
        $streetNumber = 123 + $index;
        $streetNames = [
            'Jalan Bukit Bintang', 'Jalan Raja Chulan', 'Jalan Ampang', 
            'Jalan Tun Razak', 'Jalan Sultan Ismail', 'Jalan Petaling',
            'Jalan Imbi', 'Jalan Pudu', 'Jalan Chow Kit', 'Jalan Alor'
        ];
        $address1 = $streetNumber . ', ' . $streetNames[0]; // Use first street for consistency
        
        $address2 = '';
        if ($index == 1) { // Only first index gets address2 for consistency
            $address2 = 'Apartment A-' . $index . '-5';
        }

        $postcode = '50450'; // Fixed postcode for consistency
        $city = $this->cities[0]; // Use first city for consistency
        $state = $this->states[0]; // Use first state for consistency

        return [
            'address_1' => $address1,
            'address_2' => $address2,
            'postcode' => $postcode,
            'city' => $city,
            'state' => $state
        ];
    }

    private function setAdditionalFields(Submission $submission, array $config, int $index): void
    {
        // Set receipt number
        $receiptNo = 'RCP' . str_pad($index, 6, '0', STR_PAD_LEFT);
        $submission->setAttachmentNo($receiptNo);

        // Calculate age from national ID (following the same logic as EndPointController)
        $nationalId = $submission->getNationalId();
        $yearFromIC = (int)substr(str_replace('-', '', $nationalId), 0, 2);
        $NricAge = 1900 + $yearFromIC;
        $age = (int)date('Y') - $NricAge;
        $ageRange = $this->getAgeRange($age);

        // Set fields according to business logic:
        // field1: null (not set)
        // field2: DIY ID (integration ID based on channel)
        // field3: age
        // field4: age range
        
        $submission->setField2($this->convertFieldtoQuest($config['submit_type']));
        $submission->setField3((string)$age);
        $submission->setField4($ageRange);
        
        // Set field5 for tracking
        $submission->setField5('Generated-Test-Data');
    }

    /**
     * Convert channel to DIY integration ID (mimicking EndPointController logic)
     */
    private function convertFieldtoQuest(string $channel): string
    {
        switch($channel) {
            case "MONT":
                return 'DIY_ID_MONT'; // Placeholder for app.integration_id1
            case "SHM":
                return 'DIY_ID_SHM'; // Placeholder for app.integration_id2
            case "TONT":
                return 'DIY_ID_TONT'; // Placeholder for app.integration_id3 (S99 equivalent)
            case "ECOMM":
                return 'DIY_ID_ECOMM'; // Placeholder for app.integration_id4
            case "CVSTOFT":
                return 'DIY_ID_CVSTOFT'; // Placeholder for app.integration_id5
            default:
                return 'DIY_ID_DEFAULT';
        }
    }

    /**
     * Get age range (mimicking EndPointController logic)
     */
    private function getAgeRange(int $age): string
    {
        if ($age >= 21 && $age <= 25) {
            return "21-25";
        } else if ($age >= 26 && $age <= 30) {
            return "26-30";
        } else if ($age >= 31 && $age <= 35) {
            return "31-35";
        } else if ($age >= 36 && $age <= 40) {
            return "36-40";
        } else if ($age >= 41 && $age <= 45) {
            return "41-45";
        } else if ($age >= 46 && $age <= 50) {
            return "46-50";
        } else {
            return "50>";
        }
    }

    private function createTestUser(): User
    {
        // Check if a test user already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => '60123456789']);
        
        if ($existingUser) {
            return $existingUser;
        }
        
        // Create new user if doesn't exist
        $user = new User();
        
        // Use the same data as the submission user data (matching generateUserData for index 1)
        $user->setUsername('60123456789'); // Using mobile number as username
        $user->setFullName('Ahmad Rahman');
        $user->setMobileNo('60123456789');
        $user->setEmail('ahmad.rahman@gmail.com');
        $user->setNationalId('850315-08-1234');
        $user->setGender('M');
        $user->setAge(39); // Based on birth year 1985
        $user->setRoles(['ROLE_USER']);
        
        // Set required boolean fields
        $user->setActive(true);
        $user->setGuest(false);
        $user->setVisible(true);
        $user->setDeleted(false);
        $user->setSubmissionCount(0);
        $user->setCreatedDate(new \DateTime());
        
        // Hash the password properly
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'testpassword123');
        $user->setPassword($hashedPassword);
        
        return $user;
    }
}