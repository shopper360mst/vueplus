<?php

namespace App\Command;

use App\Entity\WinnerDetails;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-winner-details',
    description: 'Create a test WinnerDetails record for testing the form',
)]
class CreateTestWinnerDetailsCommand extends Command
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
            ->addOption('expired', null, InputOption::VALUE_NONE, 'Create an expired form')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Custom UUID for the form', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $winnerDetails = new WinnerDetails();
        
        // Generate or use provided UUID
        $formUuid = $input->getOption('uuid') ?: 'test-' . uniqid();
        $winnerDetails->setFormUuid($formUuid);
        
        // Set basic data
        $winnerDetails->setSubmitCode('TEST-' . strtoupper(substr(uniqid(), -6)));
        $winnerDetails->setSubmitType('TEST');
        $winnerDetails->setStatus('pending');
        $winnerDetails->setCreatedDate(new \DateTime());
        
        // Set expiry date
        if ($input->getOption('expired')) {
            $winnerDetails->setExpiryDate(new \DateTime('-1 day'));
            $io->note('Creating expired form');
        } else {
            $winnerDetails->setExpiryDate(new \DateTime('+30 days'));
            $io->note('Creating active form (expires in 30 days)');
        }
        
        // Pre-populate some test data (optional)
        $winnerDetails->setFullName('John Doe');
        $winnerDetails->setEmail('john.doe@example.com');
        $winnerDetails->setMobileNo('0123456789');
        $winnerDetails->setNationalId('123456-12-1234');
        $winnerDetails->setAddress1('123 Test Street');
        $winnerDetails->setPostcode('50450');
        $winnerDetails->setCity('Kuala Lumpur');
        $winnerDetails->setState('Kuala Lumpur');
        $winnerDetails->setGender('Male');

        $this->entityManager->persist($winnerDetails);
        $this->entityManager->flush();

        $io->success('Test WinnerDetails record created successfully!');
        $io->table(['Field', 'Value'], [
            ['Form UUID', $formUuid],
            ['Submit Code', $winnerDetails->getSubmitCode()],
            ['Status', $winnerDetails->getStatus()],
            ['Expiry Date', $winnerDetails->getExpiryDate()->format('Y-m-d H:i:s')],
            ['Form URL', '/winner/' . $formUuid],
        ]);

        return Command::SUCCESS;
    }
}