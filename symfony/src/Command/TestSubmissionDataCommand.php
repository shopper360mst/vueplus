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
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:test-submission')]
class TestSubmissionDataCommand extends Command
{

    private $passwordEncoder;
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $encoder, ParameterBagInterface $paramBag)
    {
        $this->passwordEncoder = $encoder;
        $this->manager = $entityManager;
        $this->paramBag = $paramBag;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Generates test submission data for development and testing purposes')
            ->addArgument('week', InputArgument::OPTIONAL, 'Week number to generate data for');         
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $liveDate = $this->paramBag->get('app.live_date');
        $weekToAdd = $input->getArgument('week') ? $input->getArgument('week')-1 : 0;
        
        $weekToGenerate = new \DateTime($liveDate);
        $weekToGenerate->modify("+$weekToAdd weeks");
        $output->writeln([
            '',
            '<fg=bright-green> Adding Week '.($weekToAdd+1).' Test Submission',
        ]
        );
        // To get Parameter use 
        // $inputParameterValue = ($input->getArgument('parameter'))?$input->getArgument('parameter'):"defaultValue";
        $excelFile = "/dist/csv/csv_submission.csv";
        if (!file_exists(__DIR__.$excelFile)) {
            $output->writeln([
                '',
                'Error in finding excel file. Check your filename and path.'
            ]);
            return Command::SUCCESS;
        }

        try {            
            $reader = ReaderEntityFactory::createReaderFromFile($excelFile);
            $reader->open(__DIR__.$excelFile);
            $rowIndex = 0;
            $batchSizes = 20;
            
            $rowItem = [];
            $this->manager->getConnection()->beginTransaction();
            
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ( $sheet->getRowIterator() as $row ) {
                    $cellsPerRow = $row->getCells();
                    if ($rowIndex != 0) {                
                        
                        $FULLNAME = $cellsPerRow[0]->getValue();
                        $MOBILE_NO = $cellsPerRow[1]->getValue();
                        $USERNAME = $cellsPerRow[2]->getValue();
                        $EMAIL = $cellsPerRow[3]->getValue();
                        $NATIONAL_ID = $cellsPerRow[4]->getValue();
                        $GENDER = $cellsPerRow[5]->getValue();
                        $ADDRESS_1 = $cellsPerRow[6]->getValue();
                        $ADDRESS_2 = $cellsPerRow[7]->getValue();
                        $POSTCODE = $cellsPerRow[8]->getValue();
                        $CITY = $cellsPerRow[9]->getValue();
                        $STATE = $cellsPerRow[10]->getValue();
                        $submitCode = ['GWP','CVSTOFT'];
                        $submitType = ['SHM','CVSTOFT','ECOMM','S99','MONT'];
                        try {
                        
                            $targetEntity = new Submission();
                            $targetEntity->setFullName($FULLNAME);
                            $targetEntity->setMobileNo($MOBILE_NO);
                            $targetEntity->setEmail($EMAIL);
                            $targetEntity->setNationalId($NATIONAL_ID);
                            $targetEntity->setGender($GENDER == 'MALE' ? 'M' : 'F');
                            $targetEntity->setAddress1($ADDRESS_1);
                            $targetEntity->setAddress2($ADDRESS_2);
                            $targetEntity->setPostcode($POSTCODE);
                            $targetEntity->setCity($CITY);
                            $targetEntity->setState($STATE);
                            
                            $targetEntity->setReceiverFullName($FULLNAME);
                            $targetEntity->setReceiverMobileNo($MOBILE_NO);
                            $targetEntity->setStatus('PROCESSING');

                            // Randomly select a submit type from the array
                            $randomSubmitType = $submitType[array_rand($submitType)];
                            $targetEntity->setSubmitType($randomSubmitType);
                            
                            // For submit code, prefer 'GWP' with 70% probability
                            if (mt_rand(1, 100) <= 70) {
                                $targetEntity->setSubmitCode('GWP');
                            } else {
                                // For the remaining 30%, choose randomly from the array
                                $randomSubmitCode = $submitCode[array_rand($submitCode)];
                                $targetEntity->setSubmitCode($randomSubmitCode);
                            }

                            $targetEntity->setField2(102);
                            $NricAge = 1900 +  (int)substr($NATIONAL_ID, 0, 2);
                            $age = date('Y') - $NricAge;
                            $ageRange = $this->getAgeRange($age);
                            $targetEntity->setField3($age);
                            $targetEntity->setField4($ageRange);

                            $targetEntity->setCreatedDate($weekToGenerate);

                            $_NEWUSER = new User();
                            $_NEWUSER->setUsername($USERNAME);
                            $_NEWUSER->setPassword( 
                                $this->passwordEncoder->hashPassword(
                                    $_NEWUSER,
                                    uniqid()
                                )
                            );
                            $_NEWUSER->setFullName($FULLNAME);
                            $_NEWUSER->setMobileNo($MOBILE_NO);
                            $_NEWUSER->setEmail($EMAIL);
                            $_NEWUSER->setGuest(1);
                            $_NEWUSER->setVisible(1);
                            $_NEWUSER->setActive(1);
                            $_NEWUSER->setRoles(["ROLE_USER"]);
                            $_NEWUSER->setCreatedDate($weekToGenerate);
                            $_NEWUSER->setDeleted(0);
                            $this->manager->persist($_NEWUSER);
                            $this->manager->flush();
                            
                            $targetEntity->setUser($_NEWUSER);
                            if (($rowIndex % $batchSizes) === 0) {    
                                // Flush one shot.
                                $this->manager->persist($targetEntity);
                                $this->manager->flush(); 
                                $output->writeln([
                                    "Clearing in Batch #".($rowIndex)." per ".$batchSizes
                                ]);
                            } else {
                                // flush one by one.
                                $this->manager->persist($targetEntity);
                                $this->manager->flush();
                            }
                    
                           
                        } catch (\Exception $e) {
                            $this->manager->getConnection()->rollBack();                            
                            $output->writeln([
                                '',
                                $e->getMessage()." in Row (rollbacked)".$rowIndex + 1 
                            ]);
                            break;
                        }
                    }
                    $rowIndex++;   

                }
                $this->manager->getConnection()->commit();
                $output->writeln([
                    '',
                    '<fg=bright-green>Insertion Row(s) generated',
                ]
                );
            }
        } catch( \Exception $e) {
            $this->manager->getConnection()->rollBack();
            $output->writeln([
                $e->getMessage(),
                'Error in processing the xlxs file.'
            ]);
        }
        
        return Command::SUCCESS;
        
    }

    private function getAgeRange($age) {
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
}
