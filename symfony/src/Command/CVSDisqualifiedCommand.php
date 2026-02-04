<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\StoreItem;
use App\Entity\Submission;
use App\Service\CipherService;
use App\Service\MailerService;
use App\Service\SmsBlastService;
use App\Service\RewardMessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:cvs-disqualified',
    description: 'Processes disqualified CVS submissions from an Excel file'
)]
class CVSDisqualifiedCommand extends Command
{
    private $passwordEncoder;
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager,
     UserPasswordHasherInterface $encoder, RewardMessageService $rms, MailerService $mailer,ParameterBagInterface $paramBag,CipherService $cs,SmsBlastService $sms)
    {
        $this->em = $entityManager;
        $this->passwordEncoder = $encoder;
        $this->rms = $rms;
        $this->mailer = $mailer;
        $this->paramBag = $paramBag;
        $this->cs = $cs;
        $this->sms = $sms;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('xlsx', InputArgument::REQUIRED, 'xlxs file (/excel/cvs-winner.xlsx)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $excelFile = $input->getArgument('xlsx');

        $files = scandir(__DIR__.'/excel/');
        $output->writeln ('');
        $output->writeln ('==================== /excel/ folder files ====================');
        foreach($files as $file) {
            if($file == '.' || $file == '..') continue;
            $output->writeln ($file);
        }
        try {
            if (is_dir(__DIR__.$excelFile)) {
                $output->writeln([
                    '',
                    'Error in finding excel file. Check your filename and path.'
                ]);
                return 0;    
            }
        } catch (\Exception) {
            $output->writeln([
                '',
                'Error in finding excel file. Check your filename and path.'
            ]);
            return 0;
        }
        if (!file_exists(__DIR__.$excelFile)) {
            $output->writeln([
                '',
                'Error in finding excel file. Check your filename and path.'
            ]);
            return 0;
        }

        try {
            
            $reader = ReaderEntityFactory::createReaderFromFile($excelFile);
            $reader->open(__DIR__.$excelFile);
            $rowIndex = 0;
            $batchSizes = 2000;
            $rowItem = [];
            $this->em->getConnection()->beginTransaction();
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ( $sheet->getRowIterator() as $row ) {
                    $cellsPerRow = $row->getCells();
                    if ($rowIndex != 0) {                
                        $id = $cellsPerRow[1]->getValue();
                        $submission = $this->em->getRepository(Submission::class)->findOneBy(
                            array(            
                                "id"=> $id,
                                "field_13"=> 'CVS/TOFT',
                                'status'=>'APPROVED',
                                'field_14' =>null,
                                'field_15' =>'DISQUALIFIED'
                                )
                            );
                        if (isset($submission)) {
                            $submission->setRejectReason($cellsPerRow[15]->getValue());
                            $this->em->persist($submission);     
                            $this->em->flush();
                        }
                    }
                    $rowIndex++; 
                }
                $this->em->getConnection()->commit();
                break;
            }
        }
        catch( \Exception $e) {
            $this->em->getConnection()->rollBack();
            dd($e);
            $output->writeln([
                '',
                'Error in finding or loading the xlxs file.'
            ]);
        }

        $output->writeln([
            '',
            'CVS DISQUALIFIED Announced'
        ]
        );

        return Command::SUCCESS;
    }
}
