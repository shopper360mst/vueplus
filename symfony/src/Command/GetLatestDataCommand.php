<?php

namespace App\Command;

use App\Entity\Submission;
use App\Entity\Activity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(name: 'app:get-latest-data')]
class GetLatestDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Retrieve and display latest 500 submissions and 25 activities in console tables');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->section('SUBMISSIONS DATA');
            $io->info('Fetching latest 500 submissions...');

            $submissions = $this->entityManager->getRepository(Submission::class)
                ->createQueryBuilder('s')
                ->orderBy('s.id', 'DESC')
                ->setMaxResults(500)
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
                    'State',
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
                        $submission->getState() ?? 'N/A',
                        $submission->getCity() ?? 'N/A',
                        $submission->getCreatedDate()?->format('Y-m-d H:i:s') ?? 'N/A'
                    ]);
                }

                $table->render();
            }

            $output->writeln('');

            $io->section('ACTIVITIES DATA');
            $io->info('Fetching latest 25 activities...');

            $activities = $this->entityManager->getRepository(Activity::class)
                ->createQueryBuilder('a')
                ->orderBy('a.id', 'DESC')
                ->setMaxResults(25)
                ->getQuery()
                ->getResult();

            if (empty($activities)) {
                $io->warning('No activities found.');
            } else {
                $io->success('Found ' . count($activities) . ' activities');

                $table = new Table($output);
                $table->setHeaders([
                    'ID',
                    'Activity Name',
                    'Context Field 1',
                    'Context Field 2',
                    'Context Field 3',
                    'Username',
                    'IP',
                    'Created Date'
                ]);

                foreach ($activities as $activity) {
                    $table->addRow([
                        $activity->getId(),
                        $activity->getActivityName() ?? 'N/A',
                        $activity->getContextField1() ?? 'N/A',
                        $activity->getContextField2() ?? 'N/A',
                        $activity->getContextField3() ?? 'N/A',
                        $activity->getUsername() ?? 'N/A',
                        $activity->getIp() ?? 'N/A',
                        $activity->getCreatedDate()?->format('Y-m-d H:i:s') ?? 'N/A'
                    ]);
                }

                $table->render();
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error fetching data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
