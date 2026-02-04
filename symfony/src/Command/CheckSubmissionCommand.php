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
use Symfony\Component\Console\Helper\Table;

#[AsCommand(name: 'app:check-sub', description: 'Display top 150 latest submissions and 50 latest users in console tables')]
class CheckSubmissionCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->section('TOP 150 LATEST SUBMISSIONS');
            $io->info('Fetching top 150 latest submissions...');

            $submissions = $this->entityManager->getRepository(Submission::class)
                ->createQueryBuilder('s')
                ->orderBy('s.id', 'DESC')
                ->setMaxResults(150)
                ->getQuery()
                ->getResult();

            if (empty($submissions)) {
                $io->warning('No submissions found.');
            } else {
                $io->success('Found ' . count($submissions) . ' submissions');

                $table = new Table($output);
                $table->setHeaders([
                    'ID',
                    'Full Name',
                    'Email',
                    'Mobile No',
                    'Submit Code',
                    'Submit Type',
                    'Status',
                    'Attachment',
                    'City',
                    'Created Date'
                ]);

                foreach ($submissions as $submission) {
                    $table->addRow([
                        $submission->getId(),
                        $submission->getFullName() ?? 'N/A',
                        $submission->getEmail() ?? 'N/A',
                        $submission->getMobileNo() ?? 'N/A',
                        $submission->getSubmitCode() ?? 'N/A',
                        $submission->getSubmitType() ?? 'N/A',
                        $submission->getStatus() ?? 'N/A',
                        $submission->getAttachment() ?? 'N/A',
                        $submission->getCity() ?? 'N/A',
                        $submission->getCreatedDate()?->format('Y-m-d H:i:s') ?? 'N/A'
                    ]);
                }

                $table->render();
            }

            $output->writeln('');

            // $io->section('TOP 50 LATEST USERS');
            // $io->info('Fetching top 50 latest users...');

            // $users = $this->entityManager->getRepository(User::class)
            //     ->createQueryBuilder('u')
            //     ->orderBy('u.id', 'DESC')
            //     ->setMaxResults(50)
            //     ->getQuery()
            //     ->getResult();

            // if (empty($users)) {
            //     $io->warning('No users found.');
            // } else {
            //     $io->success('Found ' . count($users) . ' users');

            //     $table = new Table($output);
            //     $table->setHeaders([
            //         'ID',
            //         'Username',
            //         'Full Name',
            //         'Email',
            //         'Mobile No',
            //         'Type',
            //         'Category',
            //         'Is Active',
            //         'Is Guest',
            //         'Created Date'
            //     ]);

            //     foreach ($users as $user) {
            //         $table->addRow([
            //             $user->getId(),
            //             $user->getUsername() ?? 'N/A',
            //             $user->getFullName() ?? 'N/A',
            //             $user->getEmail() ?? 'N/A',
            //             $user->getMobileNo() ?? 'N/A',
            //             $user->getType() ?? 'N/A',
            //             $user->getCategory() ?? 'N/A',
            //             $user->isIsActive() ? 'Yes' : 'No',
            //             $user->isIsGuest() ? 'Yes' : 'No',
            //             $user->getCreatedDate()?->format('Y-m-d H:i:s') ?? 'N/A'
            //         ]);
            //     }

            //     $table->render();
            // }

            // $output->writeln('');
            $io->success('Command completed successfully');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error fetching data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
