<?php

namespace App\Command;

use App\Entity\ReportEntry2023;


use Shuchkin\SimpleXLSXGen;
use App\Service\ExcelUtilService;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\CipherService;
use App\Service\ActivityService;
use Psr\Log\LoggerInterface;
use App\Service\RewardMessageService;

#[AsCommand(name: 'app:extractor2023', description: 'Extracts and processes 2023 submission data for reporting purposes')]
class Extractor2023 extends Command
{
    private $manager;    
    public function __construct(
        ManagerRegistry $registry, 
        EntityManagerInterface $entityManager, 
        ParameterBagInterface $paramBag,
        ActivityService $avc,
        LoggerInterface $logger,
        CipherService $cs
    )
    {
        $this->doctrine = $registry;
        $this->manager = $entityManager;
        $this->paramBag = $paramBag;
        $this->cs = $cs;
        $this->logger = $logger;
        $this->activity = $avc;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {  
          
        $io = new SymfonyStyle($input, $output);
        try {           
            $output->writeln('');
            $output->writeln('<fg=bright-green>#################################################################################');
            $output->writeln('<fg=bright-yellow> Extracting Submission Data 2023');
            $output->writeln('<fg=bright-green>#################################################################################');
            $result = true;
            if ($result) {
                // this routine will always extract and update report_entry2024, based on current week no.
                $output->writeln('');            
                $output->writeln('<fg=bright-red>Prepping for Report Entry 2023');
                $output->writeln('');
                $excelFile = "/excel/test-2023.xlsx";
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
                    $batchSizes = 1000;
                    $rowItem = [];
                    $this->manager->getConnection()->beginTransaction();
        
                    foreach ($reader->getSheetIterator() as $sheet) {
                        foreach ( $sheet->getRowIterator() as $row ) {
                            $cellsPerRow = $row->getCells();
                            if ($rowIndex != 0) { 
                                $WEEK_NUMBER   = $cellsPerRow[0]->getValue();
                                $LAST_UPDATED  = $cellsPerRow[1]->getValue();

                                $MONT_TOTAL    = $cellsPerRow[2]->getValue();
                                $SHM_TOTAL     = $cellsPerRow[3]->getValue();
                                $S99_TOTAL     = $cellsPerRow[4]->getValue();
                                $ECOMM_TOTAL   = $cellsPerRow[5]->getValue();
                                $CVS_TOTAL     = $cellsPerRow[6]->getValue();
                                $MONT_VALID    = $cellsPerRow[7]->getValue();
                                $SHM_VALID     = $cellsPerRow[8]->getValue();
                                $S99_VALID     = $cellsPerRow[9]->getValue();
                                $ECOMM_VALID   = $cellsPerRow[10]->getValue();
                                $CVS_VALID     = $cellsPerRow[11]->getValue();

                                $MONT_INVALID    = $cellsPerRow[12]->getValue();
                                $SHM_INVALID     = $cellsPerRow[13]->getValue();
                                $S99_INVALID     = $cellsPerRow[14]->getValue();
                                $ECOMM_INVALID   = $cellsPerRow[15]->getValue();
                                $CVS_INVALID     = $cellsPerRow[16]->getValue();
                                                    
                                $MONT_PENDING    = $cellsPerRow[17]->getValue();
                                $SHM_PENDING     = $cellsPerRow[18]->getValue();
                                $S99_PENDING     = $cellsPerRow[19]->getValue();
                                $ECOMM_PENDING   = $cellsPerRow[20]->getValue();
                                $CVS_PENDING     = $cellsPerRow[21]->getValue();


                                $NEW_RPT_ENTRY = new ReportEntry2023();

                                $NEW_RPT_ENTRY->setWeekNumber($WEEK_NUMBER);
                                $NEW_RPT_ENTRY->setLastUpdated(new \DateTime);
                                $NEW_RPT_ENTRY->setMontTotal($MONT_TOTAL);
                                $NEW_RPT_ENTRY->setCvsTotal($CVS_TOTAL);
                                $NEW_RPT_ENTRY->setS99Total($S99_TOTAL);
                                $NEW_RPT_ENTRY->setEcommTotal($ECOMM_TOTAL);
                                $NEW_RPT_ENTRY->setShmTotal($SHM_TOTAL);
                
                                $NEW_RPT_ENTRY->setMontValid($MONT_VALID);
                                $NEW_RPT_ENTRY->setCvsValid($CVS_VALID);
                                $NEW_RPT_ENTRY->setS99Valid($S99_VALID);
                                $NEW_RPT_ENTRY->setEcommValid($ECOMM_VALID);
                                $NEW_RPT_ENTRY->setShmValid($SHM_VALID);
                
                                $NEW_RPT_ENTRY->setMontInvalid($MONT_INVALID);
                                $NEW_RPT_ENTRY->setCvsInvalid($CVS_INVALID);
                                $NEW_RPT_ENTRY->setS99Invalid($S99_INVALID);
                                $NEW_RPT_ENTRY->setEcommInvalid($ECOMM_INVALID);
                                $NEW_RPT_ENTRY->setShmInvalid($SHM_INVALID);
                
                                $NEW_RPT_ENTRY->setMontPending(0);
                                $NEW_RPT_ENTRY->setCvsPending(0);
                                $NEW_RPT_ENTRY->setS99Pending(0);
                                $NEW_RPT_ENTRY->setEcommPending(0);
                                $NEW_RPT_ENTRY->setShmPending(0);

                                $this->manager->persist($NEW_RPT_ENTRY);
                                $this->manager->flush();

                            }
                            $rowIndex++;

                           
                        }
                        $this->manager->getConnection()->commit();
                        break;
                    }
                    $output->writeln('2023 date entered...');

                    $reader->close();

                    if ($rowIndex) {
                        $output->writeln([
                            '',
                            'Test Data command completed'
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->manager->getConnection()->rollBack();                            
                    $output->writeln([
                        '',
                        $e->getMessage()." in Row (rollbacked) ".$rowIndex + 1 
                    ]);
                }                             
            }

        }  catch( \Exception $e) {
            $this->manager->getConnection()->rollBack();
            $output->writeln([
                '',
                'Error in finding or loading the xlxs file.'
            ]);
        }
        
        return Command::SUCCESS; 
    }

   
}