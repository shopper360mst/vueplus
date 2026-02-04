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
use App\Service\GenerateAddressService;
use Symfony\Component\Console\Question\Question;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

#[AsCommand(name: 'app:gen-address', description: 'Generates random address data for testing purposes')]
class GenAddress extends Command
{

    private $passwordEncoder;
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $encoder, private GenerateAddressService $addsv)
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
            '<fg=bright-green>##### Question : GEN ADDRESS ######################################################',
            '',
        ]);
        $question0 = new Question('<fg=bright-yellow>State data size (default:100): ');
        $_INPUTSIZE = $helper->ask($input, $output, $question0);
        $_SIZE = ($_INPUTSIZE)?$_INPUTSIZE:100;
        $question1 = new Question('<fg=bright-yellow>State state code (default:KUL): ');
        $_STATECODE_INPUT = $helper->ask($input, $output, $question1); 
        $_STATECODE = ($_STATECODE_INPUT)?$_STATECODE_INPUT:"KUL";
        
        $_OUTPUT_CSV = $this->addsv->generate($_SIZE,$_STATECODE);
        
        array_unshift($_OUTPUT_CSV , array(
            "address_1" => "address_1",
            "address_2" => "address_2",
            "postcode" => "postcode",
            "city" => "city",
            "state" => "state"
        ));

        $this->arrayToCsv($_OUTPUT_CSV);
        $output->writeln('');
        $output->writeln('<fg=bright-blue>Check output in ../Command/dist/csv/csv_address.csv');
        $output->writeln('');

        return Command::SUCCESS;
        
    }

    public function encodeFunc($value) {
        ///remove any ESCAPED double quotes within string.
        $value = str_replace('\\"','"',$value);
        //then force escape these same double quotes And Any UNESCAPED Ones.
        $value = str_replace('"','\"',$value);
        //force wrap value in quotes and return
        return '"'.$value.'"';
    }

    function fputcsv2($f, $array, $key="**") {
        $temp=fopen("php://memory","r+");
        fputcsv($temp, array_map(fn($a) => $a." ".$key, $array));
        rewind($temp);
        $line=str_replace(" ".$key, "", stream_get_contents($temp));
        fclose($temp);
        fputs($f, $line."\n");
    }

    private function arrayToXLSX(array $fields) {

    }

    private function arrayToCsv(array $fields)
    {
        $csvFile = "csv_address.csv";
        $CSV_DIR = __DIR__."/dist/csv/";
        if (!is_dir($CSV_DIR)) {
            mkdir($CSV_DIR,0755,true);
        }
        $fp = fopen($CSV_DIR.$csvFile, 'w'); 
        // Loop through file pointer and a line 
        foreach ($fields as $field) { 
            //fputcsv($fp, $field);
            fputcsv($fp, $field); 
        } 
          
        fclose($fp);
    }
}
