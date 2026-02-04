<?php

namespace App\Command;

use App\Entity\Tng;
use App\Entity\Submission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:process-tng')]
class ProcessTngCommand extends Command
{
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Process and assign TNG codes to valid submissions');
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            // Start a transaction
            $this->manager->getConnection()->beginTransaction();
            
            // Get unassigned TNGs
            $unassignedTngs = $this->manager->getRepository(Tng::class)->findUnassigned();
            $totalTngCount = count($unassignedTngs);
            
            $io->writeln("🔍 Found {$totalTngCount} unassigned TNGs to process");
            
            if ($totalTngCount == 0) {
                $io->success('No unassigned TNGs to process.');
                return Command::SUCCESS;
            }
            
            // Get eligible submissions (CVS, TOFT, or CVSTOFT)
            $allApprovedSubmissions = $this->manager->getRepository(Submission::class)->findBy(['status' => 'APPROVED']);
            $eligibleSubmissions = array_filter($allApprovedSubmissions, function($sub) {
                $submitType = $sub->getSubmitType();
                return $submitType === 'CVS' || $submitType === 'TOFT' || $submitType === 'CVSTOFT';
            });
            
            $eligibleCount = count($eligibleSubmissions);
            $io->writeln("📊 Found {$eligibleCount} eligible submissions (CVS/TOFT/CVSTOFT with APPROVED status)");
            
            if ($eligibleCount == 0) {
                $io->writeln("⚠️  No eligible submissions to assign TNGs to.");
                return Command::SUCCESS;
            }
            
            // Process assignment: cycle through eligible submissions, skip those already claimed
            $processedTngCount = 0;
            $assignedTngCount = 0;
            $submissionIndex = 0;
            
            $io->writeln("🔄 Processing TNG assignment...");
            $progressBar = $io->createProgressBar($totalTngCount);
            $progressBar->start();
            
            foreach ($unassignedTngs as $tng) {
                // Find next eligible submission without a claimed TNG
                $assigned = false;
                $attempts = 0;
                
                while (!$assigned && $attempts < count($eligibleSubmissions)) {
                    $submission = $eligibleSubmissions[$submissionIndex % count($eligibleSubmissions)];
                    $submissionIndex++;
                    
                    // Check if this submission already has an unclaimed TNG
                    $existingTng = $this->manager->getRepository(Tng::class)->findOneBy([
                        'sub_id' => $submission->getId(),
                        'is_claimed' => false
                    ]);
                    
                    if (!$existingTng) {
                        // Assign TNG to this submission
                        $tng->setSubId($submission->getId());
                        $tng->setUpdatedDate(new \DateTime());
                        $this->manager->persist($tng);
                        $assignedTngCount++;
                        $assigned = true;
                    }
                    
                    $attempts++;
                }
                
                if (!$assigned) {
                    // No eligible submission without a claimed TNG, skip this TNG
                    $io->writeln("⚠️  Could not assign TNG ID {$tng->getId()} - all submissions already have unclaimed TNGs");
                }
                
                $processedTngCount++;
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $io->newLine(2);
            
            // Flush all changes
            $this->manager->flush();
            
            $io->writeln("✅ TNG processing complete:");
            $io->writeln("   📦 Total TNGs processed: {$processedTngCount}");
            $io->writeln("   ✔️  TNGs assigned: {$assignedTngCount}");
            $io->writeln("   ⏭️  TNGs skipped: " . ($processedTngCount - $assignedTngCount));
            
            // Commit the transaction
            $this->manager->getConnection()->commit();
            $io->success("TNG Command Completed Successfully");

        } catch (\Exception $e) {
            // Only roll back if a transaction is active
            if ($this->manager->getConnection()->isTransactionActive()) {
                $this->manager->getConnection()->rollBack();
            }
            $output->writeln([
                '',
                'Error: ' . $e->getMessage(),
                'File: ' . $e->getFile() . ':' . $e->getLine()
            ]);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}