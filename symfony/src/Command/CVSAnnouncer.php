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

#[AsCommand(name: 'app:cvs-announce')]
class CVSAnnouncer extends Command
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
                        $mobile_no = $cellsPerRow[0]->getValue();
                        $submission = $this->em->getRepository(Submission::class)->findOneBy(
                            array(            
                                "mobile_no"=> $this->cs->encrypt($mobile_no),
                                "submit_type"=> 'CVSTOFT',
                                'status'=>'APPROVED',
                                'field_14' =>null,
                                'field_15' =>null
                                )
                            );
                        if (isset($submission)) {
                            $channel = $submission->getField4();
                            $submission->setIsCvsSent(true);
                            // CVS PRIZE
                            $cvs = $this->em->getRepository(StoreItem::class)->findOneBy(
                                array(            
                                    "group_name"=> $submission->getField6()
                                )
                            );
                            if ($submission->getField14() == null) {
                                $foundAnyFreeProduct = $this->em->getRepository(Product::class)->findAndLock($submission->getUser(),$cvs);
                                
                                if($foundAnyFreeProduct){
                                    $this->rms->sendRewardMessage ( 
                                        $foundAnyFreeProduct->getProductType(), 
                                        $foundAnyFreeProduct->getId(), 
                                        $submission->getUser(), 
                                        $foundAnyFreeProduct->getProductExternalCode(), 
                                        $foundAnyFreeProduct->getStoreItem()->getId(),
                                        $foundAnyFreeProduct->getExpiryDate()
                                    );
                                }
                                $foundAnyFreeProduct->setDeliveryStatus('ADDRESS');
                                $this->em->persist($foundAnyFreeProduct);     
                                $this->em->flush();
                                $submission->setField14($foundAnyFreeProduct->getId());
                                
                                // $start_msg = "Hi ".$submission->getUser()->getFullName().",<BR><BR>";
                                // $mid_msg = $this->paramBag->get('app.email_contest')."<BR><BR><BR>";
                                // $end_msg = "1664 Malaysia";

                                // $message = $start_msg.$mid_msg.$end_msg; 
                                // $this->mailer->sendTwigEmail(
                                //     $message, 
                                //     [$submission->getUser()->getEmail()], 
                                //     $this->paramBag->get('app.email_user'),
                                //     $this->paramBag->get('app.email_title_contest'), 
                                //     "", 
                                //     false
                                // );
                                // $msg1 = $this->paramBag->get('app.sms_start').$this->paramBag->get('app.sms_contest');
                                // $this->sms->addToQueue($submission->getUser()->getMobileNo(), $msg1 );
                                $this->em->persist($submission);     
                                $this->em->flush();
                            }
                        }
                    }
                    $rowIndex++; 
                }
                $this->em->getConnection()->commit();
                break;
            }
            //ANNOUNCE ALL NO WIN
            $allSubmission = $this->em->getRepository(Submission::class)->findBy(
                array(            
                    "field_13"=> 'CVS/TOFT',
                    "field_14"=>NULL,
                    "field_15"=>NULL,
                    'status'=>'APPROVED'
                )
            );
            for($i = 0;$i<count($allSubmission);$i++){
                $allSubmission[$i]->setField15('DISQUALIFIED');
                $this->em->persist($allSubmission[$i]);     
                $this->em->flush();
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
            'CVS Announced'
        ]
        );

        return Command::SUCCESS;
    }
}
