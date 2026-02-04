<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Submission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:test-submission-insert')]
class TestSubmissionInsertCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private UserPasswordHasherInterface $passwordHasher;

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

    protected function configure(): void
    {
        $this
            ->setDescription('Test submission insertion with validation')
            ->addArgument('full_name', InputArgument::REQUIRED, 'Full name of the submitter')
            ->addArgument('mobile_no', InputArgument::REQUIRED, 'Mobile number (Malaysian format)')
            ->addArgument('email', InputArgument::REQUIRED, 'Email address')
            ->addArgument('national_id', InputArgument::REQUIRED, 'National ID (Malaysian IC format)')
            ->addArgument('address_1', InputArgument::REQUIRED, 'Primary address')
            ->addOption('receiver_full_name', null, InputOption::VALUE_OPTIONAL, 'Receiver full name (defaults to same as submitter)')
            ->addOption('receiver_mobile_no', null, InputOption::VALUE_OPTIONAL, 'Receiver mobile number (defaults to same as submitter)')
            ->addOption('address_2', null, InputOption::VALUE_OPTIONAL, 'Secondary address')
            ->addOption('postcode', null, InputOption::VALUE_OPTIONAL, 'Postcode')
            ->addOption('city', null, InputOption::VALUE_OPTIONAL, 'City')
            ->addOption('state', null, InputOption::VALUE_OPTIONAL, 'State')
            ->addOption('gender', null, InputOption::VALUE_OPTIONAL, 'Gender (M/F)', 'M')
            ->addOption('submit_type', null, InputOption::VALUE_OPTIONAL, 'Submit type', 'TEST')
            ->addOption('submit_code', null, InputOption::VALUE_OPTIONAL, 'Submit code', 'TEST001')
            ->addOption('validate-only', null, InputOption::VALUE_NONE, 'Only validate without saving to database')
            ->addOption('create-user', null, InputOption::VALUE_NONE, 'Create a test user for this submission');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get required arguments
        $fullName = $input->getArgument('full_name');
        $mobileNo = $input->getArgument('mobile_no');
        $email = $input->getArgument('email');
        $nationalId = $input->getArgument('national_id');
        $address1 = $input->getArgument('address_1');

        // Get optional arguments with defaults
        $receiverFullName = $input->getOption('receiver_full_name') ?: $fullName;
        $receiverMobileNo = $input->getOption('receiver_mobile_no') ?: $mobileNo;
        $address2 = $input->getOption('address_2');
        $postcode = $input->getOption('postcode');
        $city = $input->getOption('city');
        $state = $input->getOption('state');
        $gender = $input->getOption('gender');
        $submitType = $input->getOption('submit_type');
        $submitCode = $input->getOption('submit_code');

        $validateOnly = $input->getOption('validate-only');
        $createUser = $input->getOption('create-user');

        $io->title('Testing Submission Insert with Validation');

        // Display the data that will be inserted
        $io->section('Submission Data:');
        $io->table(
            ['Field', 'Value'],
            [
                ['Full Name', $fullName],
                ['Mobile No', $mobileNo],
                ['Email', $email],
                ['National ID', $nationalId],
                ['Address 1', $address1],
                ['Address 2', $address2 ?: 'N/A'],
                ['Postcode', $postcode ?: 'N/A'],
                ['City', $city ?: 'N/A'],
                ['State', $state ?: 'N/A'],
                ['Gender', $gender],
                ['Receiver Full Name', $receiverFullName],
                ['Receiver Mobile No', $receiverMobileNo],
                ['Submit Type', $submitType],
                ['Submit Code', $submitCode],
            ]
        );

        try {
            // Create submission entity
            $submission = new Submission();
            
            // Set all the data
            $submission->setFullName($fullName);
            $submission->setMobileNo($mobileNo);
            $submission->setEmail($email);
            $submission->setNationalId($nationalId);
            $submission->setAddress1($address1);
            $submission->setReceiverFullName($receiverFullName);
            $submission->setReceiverMobileNo($receiverMobileNo);
            
            if ($address2) {
                $submission->setAddress2($address2);
            }
            if ($postcode) {
                $submission->setPostcode($postcode);
            }
            if ($city) {
                $submission->setCity($city);
            }
            if ($state) {
                $submission->setState($state);
            }
            
            $submission->setGender($gender);
            $submission->setSubmitType($submitType);
            $submission->setSubmitCode($submitCode);
            $submission->setStatus('PROCESSING');
            $submission->setCreatedDate(new \DateTime());

            // Create user if requested
            $user = null;
            if ($createUser) {
                $user = new User();
                $user->setUsername('test_' . uniqid());
                $user->setPassword($this->passwordHasher->hashPassword($user, 'test123'));
                $user->setFullName($fullName);
                $user->setMobileNo($mobileNo);
                $user->setEmail($email);
                $user->setGuest(1);
                $user->setVisible(1);
                $user->setActive(1);
                $user->setRoles(["ROLE_USER"]);
                $user->setCreatedDate(new \DateTime());
                $user->setDeleted(0);
                
                $submission->setUser($user);
            }

            // Validate the submission
            $io->section('Validation Results:');
            $violations = $this->validator->validate($submission);

            if (count($violations) > 0) {
                $io->error('Validation failed with ' . count($violations) . ' error(s):');
                
                $errorTable = [];
                foreach ($violations as $violation) {
                    $errorTable[] = [
                        $violation->getPropertyPath(),
                        $violation->getMessage(),
                        $violation->getInvalidValue()
                    ];
                }
                
                $io->table(['Property', 'Error Message', 'Invalid Value'], $errorTable);
                
                return Command::FAILURE;
            } else {
                $io->success('✅ All validation constraints passed!');
            }

            // Test encryption/decryption
            $io->section('Encryption/Decryption Test:');
            $io->table(
                ['Field', 'Original', 'Encrypted', 'Decrypted', 'Match'],
                [
                    [
                        'Full Name',
                        $fullName,
                        substr($submission->encrypt($fullName), 0, 20) . '...',
                        $submission->getFullName(),
                        $submission->getFullName() === $fullName ? '✅' : '❌'
                    ],
                    [
                        'Email',
                        $email,
                        substr($submission->encrypt($email), 0, 20) . '...',
                        $submission->getEmail(),
                        $submission->getEmail() === $email ? '✅' : '❌'
                    ],
                    [
                        'Mobile No',
                        $mobileNo,
                        substr($submission->encrypt($mobileNo), 0, 20) . '...',
                        $submission->getMobileNo(),
                        $submission->getMobileNo() === $mobileNo ? '✅' : '❌'
                    ]
                ]
            );

            if ($validateOnly) {
                $io->note('Validation-only mode: No data was saved to the database.');
                return Command::SUCCESS;
            }

            // Save to database
            $io->section('Database Operations:');
            
            $this->entityManager->beginTransaction();
            
            try {
                if ($user) {
                    $this->entityManager->persist($user);
                    $io->writeln('✅ User entity prepared for persistence');
                }
                
                $this->entityManager->persist($submission);
                $this->entityManager->flush();
                $this->entityManager->commit();
                
                $io->success('✅ Submission successfully saved to database!');
                $io->writeln('Submission ID: ' . $submission->getId());
                
                if ($user) {
                    $io->writeln('User ID: ' . $user->getId());
                    $io->writeln('Username: ' . $user->getUsername());
                }

            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $io->error('❌ Database error: ' . $e->getMessage());
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('❌ Unexpected error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}