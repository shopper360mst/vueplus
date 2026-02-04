<?php

namespace App\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Entity\Postal;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

#[AsCommand(name: 'app:install-postal-data', description: 'Imports postal code data from Excel file into the database')]
class InstallPostalData extends Command
{
    //protected static $defaultName = 'app:install-postal-data';
    private $passwordEncoder;
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $encoder)
    {
        $this->passwordEncoder = $encoder;
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        // $this->addArgument('parameter', InputArgument::OPTIONAL, 'Parameter required');        
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // To get Parameter use 
        // $inputParameterValue = ($input->getArgument('parameter'))?$input->getArgument('parameter'):"defaultValue";
        $excelFile = "/excel/postal-data.xlsx";
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
            $batchSizes = 500;
            
            $rowItem = [];
            $this->manager->getConnection()->executeStatement('TRUNCATE TABLE postal');
            $this->manager->getConnection()->beginTransaction();
            
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ( $sheet->getRowIterator() as $row ) {
                    $cellsPerRow = $row->getCells();
                    if ($rowIndex != 0) {                
                        
                        $_POSTCODE  = $cellsPerRow[0]->getValue();
                        $_CITY      = $cellsPerRow[1]->getValue();
                        $_STATECODE = $cellsPerRow[2]->getValue();
                        $_STATE     = $cellsPerRow[3]->getValue();
                        
                        try {
                        
                            $targetEntity = new Postal();
                            $targetEntity->setPostcode($_POSTCODE);
                            $targetEntity->setCity($_CITY);
                            $targetEntity->setStateCode($_STATECODE);
                            $targetEntity->setState($_STATE);
                            
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
                '',
                'Error in processing the xlxs file.'
            ]);
        }
        
        return Command::SUCCESS;
        
    }
}
