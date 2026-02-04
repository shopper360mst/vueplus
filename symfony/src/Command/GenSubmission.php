<?php

namespace App\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Postal;
use Symfony\Component\Console\Question\Question;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use App\Service\GenerateAddressService;
use App\Service\GenerateUserService;
use Shuchkin\SimpleXLSXGen;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:gen-submission', description: 'Generates test submission data based on distribution settings in Excel file')]
class GenSubmission extends Command
{

    private $passwordEncoder;
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $encoder, private GenerateAddressService $addsv, private GenerateUserService $usersv)
    {
        $this->passwordEncoder = $encoder;
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $helper = $this->getHelper('question');
        $output->writeln([
            '',
            '',
            '<fg=bright-green>##### Question ############################################################################################',
            '',
            '<fg=bright-yellow>  Note: Please modify the submission-distribution.xlsx file to define data distributions',
            '',            
            '<fg=bright-green>###########################################################################################################',
            '',
        ]);
         
        $excelFile = "/excel/submission-distribution.xlsx";
        if (!file_exists(__DIR__.$excelFile)) {
            $output->writeln([
                '',
                'Error in finding submission-distribution excel file. Check your filename and path.'
            ]);
            return Command::SUCCESS;
        }

        try {
            $reader = ReaderEntityFactory::createReaderFromFile($excelFile);
            $reader->open(__DIR__.$excelFile);
            $rowIndex = 0;
            $batchSizes = 1000;
            $rowItem = [];
            $_STATES_ALLOCATIONS = [
                array(
                    "label" => "SIZE",
                    "value" => 0,
                ),
                array(
                    "label" => "PLS",
                    "value" => 0,
                ),
                array(
                    "label" => "KDH",
                    "value" => 0,
                ),
                array(
                    "label" => "PNG",
                    "value" => 0,
                ),
                array(
                    "label" => "KTN",
                    "value" => 0,
                ),
                array(
                    "label" => "TRG",
                    "value" => 0,
                ),
                array(
                    "label" => "PHG",
                    "value" => 0,
                ),
                array(
                    "label" => "PRK",
                    "value" => 0,
                ),
                array(
                    "label" => "SGR",
                    "value" => 0,
                ),
                array(
                    "label" => "KUL",
                    "value" => 0,
                ),
                array(
                    "label" => "PJY",
                    "value" => 0,
                ),
                array(
                    "label" => "NSN",
                    "value" => 0,
                ),
                array(
                    "label" => "MLK",
                    "value" => 0,
                ),
                array(
                    "label" => "JHR",
                    "value" => 0,
                ),
                array(
                    "label" => "LBN",
                    "value" => 0,
                ),
                array(
                    "label" => "SBH",
                    "value" => 0,
                ),
                array(
                    "label" => "SRW",
                    "value" => 0,
                )
            ];
            $_GENDER_ALLOCATIONS = [
                array(
                    "male" => 0,
                    "female" => 0
                )
            ];
            $this->manager->getConnection()->beginTransaction();
            $rowIndex = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row ) {
                    $cellsPerRow = $row->getCells();                    
                    if ($rowIndex == 1) {
                        $_STATES_ALLOCATIONS[0]['value'] = ($cellsPerRow[0]->getValue())?$cellsPerRow[0]->getValue():0;
                        for ($i = 1; $i <= 16; $i++) {
                            $_STATES_ALLOCATIONS[$i]['value'] = $cellsPerRow[$i]->getValue(); 
                        }

                        // $_GENDER_ALLOCATIONS = [
                        //     array(
                        //         "male" => $cellsPerRow[18]->getValue(),
                        //         "female" => $cellsPerRow[19]->getValue()
                        //     )
                        // ];
                    }
                    $rowIndex++;
                }
            }
            $_ALL_RESULTS = [];
            $_SIZE = $_STATES_ALLOCATIONS[0]['value'];

            $OUTPUT_USER_RESULT = $this->usersv->generate( $_SIZE,"a","y" );
            $OUTPUT_ADDRESS_RESULT = [];
            $OUTPUT_ADDRESS_FLAT = [];
            for($s = 1;$s <= 16; $s++) {
                if ($_STATES_ALLOCATIONS[$s]['value']) {
                    array_push($OUTPUT_ADDRESS_RESULT, $this->addsv->generate(
                        $_STATES_ALLOCATIONS[$s]['value'],
                        $_STATES_ALLOCATIONS[$s]['label']
                    ));
                }
            }

            for($x = 0;$x < count( $OUTPUT_ADDRESS_RESULT ); $x++) {
                for($y = 0;$y < count( $OUTPUT_ADDRESS_RESULT[$x] ); $y++) {
                    array_push($OUTPUT_ADDRESS_FLAT,$OUTPUT_ADDRESS_RESULT[$x][$y]);
                }              
            }
            $_OUTPUT_CSV = [];
            for($z = 0;$z < count( $OUTPUT_USER_RESULT ); $z++) {
                array_push( $_OUTPUT_CSV, array_merge($OUTPUT_USER_RESULT[$z],$OUTPUT_ADDRESS_FLAT[$z]) );
            }

            array_unshift($_OUTPUT_CSV , array(
                "full_name" => "full_name",
                "mobile_no" => "mobile_no",
                "username" => "username",                
                "email" => "email",
                "national_id" => "national_id",
                "gender" => "gender",
                "address_1" => "address_1",
                "address_2" => "address_2",
                "postcode" => "postcode",
                "city" => "city",
                "state" => "state"
            ));
    
            $this->arrayToCsv($_OUTPUT_CSV);
    
         
        } catch( \Exception $e) {
            $this->manager->getConnection()->rollBack();
            $output->writeln([
                $e->getMessage(),
                'Error in processing the xlxs file.'
            ]);
        }         

        $output->writeln('');
        $output->writeln('<fg=bright-blue>Check output in ../Command/dist/csv/csv_submission.csv');
        $output->writeln('');

        return Command::SUCCESS;
     
        
    }

    function fputcsv2($f, $array, $key="**") {
        $temp=fopen("php://memory","r+");
        fputcsv($temp, array_map(fn($a) => $a." ".$key, $array));
        rewind($temp);
        $line=str_replace(" ".$key, "", stream_get_contents($temp));
        fclose($temp);
        fputs($f, $line);
    }

    private function arrayToXLSX(array $fields) {

    }

    private function arrayToCsv(array $fields)
    {
        $csvFile = "csv_submission.csv";
        $CSV_DIR = __DIR__."/dist/csv/";
        if (!is_dir($CSV_DIR)) {
            mkdir($CSV_DIR,0755,true);
        }
        $fp = fopen($CSV_DIR.$csvFile, 'w'); 
        // Loop through file pointer and a line 
        foreach ($fields as $field) { 
            fputcsv($fp, $field); 
        } 
          
        fclose($fp);
    }
}
