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
use App\Service\GenerateUserService;
use Symfony\Component\Console\Question\Question;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

#[AsCommand(name: 'app:gen-user', description: 'Generates random user data for testing purposes')]
class GenUser extends Command
{

    private $passwordEncoder;
    private $manager;
    protected $faker;
    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $encoder, private GenerateUserService $usersv)
    {
        $this->passwordEncoder = $encoder;
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    public function numberBetween(int $min = 0, int $max = 2147483647): int
    {
        $int1 = min($min, $max);
        $int2 = max($min, $max);

        return rand($int1, $int2);
    }

    private function myKadNumber($gender = null, $hyphen = false)
    {
        // year of birth
        $yy = $this->numberBetween(0, 99);

        // month of birth
        $fDate = new \DateTime;
        $mm = $fDate->format('m');

        // day of birth
        $dd = $fDate->format('d');;

        // place of birth (1-59 except 17-20)
        while (in_array($pb = $this->numberBetween(1, 59), [17, 18, 19, 20], false)) {
        }

        // random number
        $nnn = $this->numberBetween(0, 999);

        // gender digit. Odd = MALE, Even = FEMALE
        $g = $this->numberBetween(0, 9);

        //Credit: https://gist.github.com/mauris/3629548
        if ($gender === "male") {
            $g |= 1;
        } elseif ($gender === "female") {
            $g &= ~1;
        }

        // formatting with hyphen
        if ($hyphen === true) {
            $hyphen = '-';
        } elseif ($hyphen === false) {
            $hyphen = '';
        }

        return sprintf('%02d%02d%02d%s%02d%s%03d%01d', $yy, $mm, $dd, $hyphen, $pb, $hyphen, $nnn, $g);
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $helper = $this->getHelper('question');
        $output->writeln([
            '',
            '',
            '<fg=bright-green>##### Question : GEN USER ######################################################',
            '',
        ]);
        $question0 = new Question('<fg=bright-yellow>State data size (default:100): ');
        $_INPUTSIZE = $helper->ask($input, $output, $question0);
        $question1 = new Question('<fg=bright-yellow>Mobile safe numbers? (default:n): ');
        $_MOBILE_SAFE = $helper->ask($input, $output, $question1);
        $_MOBILE_SAFE = ($_MOBILE_SAFE)?$_MOBILE_SAFE:"n";
        $_MOBILE_SAFE = ($_MOBILE_SAFE != "n")?true:false;
        $_SIZE = ($_INPUTSIZE)?$_INPUTSIZE:100;

        $question2 = new Question('<fg=bright-yellow>State gender? (default:any): ');
        $_GENDER = $helper->ask($input, $output, $question2);
        if ($_GENDER == "m") {
            $_GENDER = 1;
        } else if ($_GENDER == "f"){
            $_GENDER = 0;
        } else {
            $_GENDER = 2;
        }
  
        $_OUTPUT_CSV = $this->usersv->generate($_SIZE ,$_GENDER, $_MOBILE_SAFE);

        array_unshift($_OUTPUT_CSV , array(
            "full_name" => "full_name",
            "mobile_no" => "mobile_no",
            "username" => "username",                
            "email" => "email",
            "national_id" => "national_id",
            "gender" => "gender"
        ));

        $this->arrayToCsv($_OUTPUT_CSV);

        $output->writeln('');
        $output->writeln('<fg=bright-blue>Check output in ../Command/dist/csv/csv_user.csv');
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
        $csvFile = "csv_user.txt";
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
