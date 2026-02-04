<?php

namespace App\Command;

use App\Entity\Submission;
use App\Entity\TrxSubmission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'app:process-submission-to-trx')]
class ProcessSubmissionToTrxCommand extends Command
{
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Reads submissions with "processing" status and inserts them into trx_submission table');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Find all submissions with status "processing"
            $submissions = $this->manager->getRepository(Submission::class)->findBy([
                'status' => 'PROCESSING'
            ]);
            
            if (!$submissions || count($submissions) === 0) {
                $io->warning('No submissions with "processing" status found.');
                return Command::SUCCESS;
            }
            
            $count = 0;
            foreach ($submissions as $submission) {
                // Check if this submission is already in trx_submission
                $existingTrx = $this->manager->getRepository(TrxSubmission::class)->findOneBy([
                    'sub_id' => $submission->getId(),
                    'is_completed' => false
                ]);
                
                if ($existingTrx) {
                    $io->note(sprintf('Submission ID %d already exists in trx_submission table.', $submission->getId()));
                    continue;
                }
                $status = ['APPROVED','REJECTED'];
                $rejectReasons = ['Testing','Outside contest period','Duplicate receipt','Invalid receipt','Illegible product',
                'Receipt not clear','INSUFFICIENT PURCHASE QUANTITY','ILLEGIBLE OUTLET','INCOMPLETE INFORMATION',
                'INSUFFICIENT PURCHASE AMOUNT'];
                
                // Create new TrxSubmission entity
                $trxSubmission = new TrxSubmission();
                $trxSubmission->setSubId($submission->getId());
                
                // Randomly select status with 80% chance of APPROVED
                $randomStatus = (mt_rand(1, 100) <= 80) ? 'APPROVED' : 'REJECTED';
                $trxSubmission->setSubStatus($randomStatus);
                
                // Set reject reason only if status is REJECTED
                $rejectReason = null;
                if ($randomStatus === 'REJECTED') {
                    // Randomly select a reject reason from the array
                    $rejectReason = $rejectReasons[array_rand($rejectReasons)];
                }
                $trxSubmission->setRejectReason($rejectReason);
                
                $trxSubmission->setDiyId(rand(1000, 9999)); // Random integer as per requirement
                $trxSubmission->setSubmitType($submission->getSubmitType());
                $trxSubmission->setEntry('0'); // Set entry to 0 as per requirement
                $trxSubmission->setCompleted(false); // Not completed yet
                $trxSubmission->setCompletedDate(null); // No completion date yet
                
                $this->manager->persist($trxSubmission);
                $count++;
            }
            
            $this->manager->flush();
            
            $io->success(sprintf('Successfully processed %d submissions to trx_submission table.', $count));
            
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}