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
use Symfony\Component\Console\Question\Question;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:form-gen', description: 'Generates form templates and JavaScript from Excel specifications')]
class FormGen extends Command
{
    //protected static $defaultName = 'app:form-gen';
    private $passwordEncoder;
    private $manager;
    
    public function __construct(EntityManagerInterface $entityManager, private ParameterBagInterface $paramBag)
    {
        $this->manager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        // $this->addArgument('parameter', InputArgument::OPTIONAL, 'Parameter required');        
    }

    private function generatePayLoad($payLoad, $index, $component) {
        if ($payLoad != "") {
            if ($component == "file-upload") {
                return "                ".$payLoad.": this.form_fields[0]['form_group'][".$index."].data_url,\r\n";
            } else if ($component == "mobile-prefix") {
                return "                ".$payLoad.": this.form_fields[0]['form_group'][".$index."].prefix_value + this.form_fields[0]['form_group'][".$index."].value,\r\n";
            } else {
                return "                ".$payLoad.": this.form_fields[0]['form_group'][".$index."].value,\r\n";
            }
            
        } else {
            return "";
        }
        
    }

    private function processOptions($options) {
        $pattern = '/\{(.*?)\}/';
        preg_match_all($pattern, $options, $matches);
        $strContruct = "";
        if ($matches) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                if ($i == count($matches[1])-1) {
                    $strContruct .= '{"label":"'.explode("|",$matches[1][$i])[0].'","value":"'.explode("|",$matches[1][$i])[1].'"}' ;

                } else {
                    $strContruct .= '{"label":"'.explode("|",$matches[1][$i])[0].'","value":"'.explode("|",$matches[1][$i])[1].'"},' ;

                }
            }
            return "[".$strContruct."]";
        } else {
            return $strContruct = "null";
        }
    }
 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // To get Parameter use 
        // $inputParameterValue = ($input->getArgument('parameter'))?$input->getArgument('parameter'):"defaultValue";
        $helper = $this->getHelper('question');
        $_REF_XCEL = "";
        $output->writeln([
            '',
            '',
            '<fg=bright-green>###################################################### Question ######################################################',
            '',
        ]);
        $question0 = new Question('<fg=bright-yellow>Template xlsx file name , if is the same (form-generator-template.xlsx) just press enter : ');
        $_REF_XCEL = $helper->ask($input, $output, $question0);

        $_FILENAME = ($_REF_XCEL)?$_REF_XCEL:"form-generator-template.xlsx";
         
        $excelFile = "//excel//".$_FILENAME;
        if (!file_exists(__DIR__.$excelFile)) {
            $output->writeln([
                '',
                '<fg=bright-red>Error in finding excel file. Make sure form-generator-template excel file is in /excel folder within /Command.'
            ]);
            return Command::SUCCESS;
        }

      
        $question1 = new Question('<fg=bright-yellow> Please enter desired alpine x-data (e.g. formContestAlpine) : ');
        $_XDATA_NAME = $helper->ask($input, $output, $question1);

        $question2 = new Question('<fg=bright-yellow> Please enter final file name (e.g. form.contest.alpine w/o extension) : ');
        $_FINAL_FILE = $helper->ask($input, $output, $question2);
        $output->writeln([
            '',
            '<fg=bright-green>#######################################################################################################################',
            '',
        ]);
        try {            
            $reader = ReaderEntityFactory::createReaderFromFile($excelFile);
            $reader->open(__DIR__.$excelFile);
            $rowIndex = 0;            
            $rowItem = [];
            
            $pathSchema  = $this->paramBag->get('kernel.project_dir').'//src//Command//ref//schema.txt';
            $pathJSFile  = $this->paramBag->get('kernel.project_dir').'//src//Command//ref//form.controller.js.txt';
            $pathTWGFile = $this->paramBag->get('kernel.project_dir').'//src//Command//ref//form.twig.txt';

            $pathOutput = $this->paramBag->get('kernel.project_dir').'//src//Command//dist//';                            
            
            $actualFormSchema   = file_get_contents($pathSchema);
            $actualSrcFile      = file_get_contents($pathJSFile);
            $actualTwgFile      = file_get_contents($pathTWGFile);
            
            
            $_ITERATOR_DATA     = "";
            $_STRING_DATA       = "";
            $_FINAL_STRING      = "";
            $_PAYLOAD_FORMATTED = "";
            foreach ($reader->getSheetIterator() as $sheet) {                
                if ($sheet->getName() === 'Data') {
                    foreach ( $sheet->getRowIterator() as $row ) {
                        $cellsPerRow = $row->getCells();
                        if ($rowIndex != 0) {                
                            
                            $_NAME      = $cellsPerRow[0]->getValue();
                            $_LABEL     = (null != $cellsPerRow[1]->getValue())?$cellsPerRow[1]->getValue():"";
                            $_COMPONENT = $cellsPerRow[2]->getValue();
                            $_TYPE      = (null != $cellsPerRow[3]->getValue())?$cellsPerRow[3]->getValue():"";
                            $_REQUIRED  = (null != $cellsPerRow[4]->getValue())?$cellsPerRow[4]->getValue():0;
                            $_DEFAULT   = (null != $cellsPerRow[5]->getValue())?$cellsPerRow[5]->getValue():"";
                            $_OPTIONS   = (null != $cellsPerRow[6]->getValue())?$cellsPerRow[6]->getValue():"";
                            $_MESSAGE   = (null != $cellsPerRow[7]->getValue())?$cellsPerRow[7]->getValue():"";
                            $_MAXLENGTH = (null != $cellsPerRow[8]->getValue())?$cellsPerRow[8]->getValue():"\"\"";
                            $_PAYLOAD   = (null != $cellsPerRow[9]->getValue())?$cellsPerRow[9]->getValue():"";

                            $_DISABLED  = 0;                        

                            try {
                                $pathSchema = $this->paramBag->get('kernel.project_dir').'\\src\\Command\\src\\schema.txt';
                                $_ITERATOR_DATA .= "{\r\n";
                                $_STRING_DATA  = $actualFormSchema;

                                $_STRING_DATA = str_replace("<INDEX>", $rowIndex - 1, $_STRING_DATA);
                                $_STRING_DATA = str_replace("<NAME>", $_NAME, $_STRING_DATA);
                                $_STRING_DATA = str_replace("<LABEL>", $_LABEL, $_STRING_DATA);
                                $_STRING_DATA = str_replace("<MESSAGE>", $_MESSAGE, $_STRING_DATA);
                                $_STRING_DATA = str_replace("<MAXLENGTH>", $_MAXLENGTH, $_STRING_DATA);
                                $_STRING_DATA = str_replace("<COMPONENT>", $_COMPONENT, $_STRING_DATA);
                                $_STRING_DATA = str_replace("<VALUE>", $_DEFAULT, $_STRING_DATA);
                                $_STRING_DATA = str_replace("<TYPE>", $_TYPE, $_STRING_DATA);
                                $_STRING_DATA = str_replace("<REQUIRED>", ($_REQUIRED)?"true":"false", $_STRING_DATA);
                                $_STRING_DATA = str_replace("<DISABLED>", "false", $_STRING_DATA);


                                if ($_COMPONENT == "mobile-prefix") {
                                    $_STRING_DATA = str_replace("<PREFIX>", "60", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<PLACEHOLDER>", "E.g. 127654321", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<MASK>", "199999999", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<OPTIONS>", "[{\"label\":\"60\",\"value\":60},{\"label\":\"65\",\"value\":65}]\r\n", $_STRING_DATA);
                                                                    
                                } else if ($_COMPONENT == "nricppt") {
                                    $_STRING_DATA = str_replace("<PREFIX>", "NRIC", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<PLACEHOLDER>", "e.g. 999999-99-9999", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<MASK>", "999999-99-9999", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<OPTIONS>", "[{\"label\":\"IC No.\", value:\"NRIC\", mask:\"999999-99-9999\",placeholder:\"e.g. 999999-99-9999\"},{\"label\":\"Passport\",\"value\":\"PASSPORT\",\"mask\":\"\",\"placeholder\":\"e.g. A987654321\"}]\r\n", $_STRING_DATA);
                                    
                                } else if ($_COMPONENT == "adv-select") {
                                    $_STRING_DATA = str_replace("<PREFIX>", "", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<OPTIONS>", "[".$this->processOptions($_OPTIONS)."\r\n", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<MASK>", "", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<PLACEHOLDER>", "", $_STRING_DATA);
                                    
                                    
                                } else if ($_COMPONENT == "select") {
                                    $_STRING_DATA = str_replace("<PREFIX>", "", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<OPTIONS>", $this->processOptions($_OPTIONS)."\r\n", $_STRING_DATA);                                    
                                    $_STRING_DATA = str_replace("<MASK>", "", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<PLACEHOLDER>", "", $_STRING_DATA);
                                    
                                } else if ($_COMPONENT == "dual-switch") {
                                    $_STRING_DATA = str_replace("<PREFIX>", "", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<OPTIONS>", $this->processOptions($_OPTIONS)."\r\n", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<MASK>", "", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<PLACEHOLDER>", "", $_STRING_DATA);
                                    
                                } else {
                                    $_STRING_DATA = str_replace("<PREFIX>", "", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<MASK>", "", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<PLACEHOLDER>", $_LABEL." Here...", $_STRING_DATA);
                                    $_STRING_DATA = str_replace("<OPTIONS>", "null\r\n", $_STRING_DATA);                                    
                                }
                                $_ITERATOR_DATA .= $_STRING_DATA;
                                $_ITERATOR_DATA .= "                },";
                                
                                $_PAYLOAD_FORMATTED .= ($this->generatePayLoad($_PAYLOAD, $rowIndex - 1, $_COMPONENT))?$this->generatePayLoad($_PAYLOAD, $rowIndex - 1, $_COMPONENT):"";
                                $_STRING_FS = str_replace("<FORM_ATTRIBUTES>", $_ITERATOR_DATA, $actualSrcFile);
                                
                            } catch (\Exception $e) {
                                $output->writeln([
                                    '',
                                    $e->getMessage()." in Data Row > ".$rowIndex + 1 
                                ]);
                                break;
                            }
                        }
                        $rowIndex++;
                    }

                    $_FINAL_STRING = str_replace("<FORM_XDATA>", $_XDATA_NAME, $_STRING_FS );
                    $_FINAL_STRING = str_replace("<PAYLOAD>", $_PAYLOAD_FORMATTED, $_FINAL_STRING );
                    file_put_contents($pathOutput.$_FINAL_FILE.".txt", $_FINAL_STRING);

                    $_FINAL_TWIG = str_replace("<FORM_XDATA>", $_XDATA_NAME, $actualTwgFile );
                    $_TWIG_FILENAME = str_replace(".alpine","",$_FINAL_FILE );

                    file_put_contents($pathOutput."//twig//".$_TWIG_FILENAME.".html.twig.txt", $_FINAL_TWIG);


                    $output->writeln([
                        '<fg=bright-green>Code </><fg=bright-red>'.$_FINAL_FILE.'.txt available in </><fg=bright-green>/dist/.</>',
                        '<fg=bright-green>Code </><fg=bright-red>'.$_TWIG_FILENAME.'.html.twig.txt available in </><fg=bright-green>/dist/twig.</>',
                        '',
                        '<fg=bright-blue>Form twigs are best included into page twig where the design of the whole page is incorporate. Place them into /templates/form</>',
                        '',                        
                    ]);
                    $output->writeln("<fg=bright-red>Example page with form twig implementation\n");
                    $output->writeln("#######################################################################################################################");
                    $output->writeln("<fg=bright-green>");
                    $output->writeln("{% extends 'base.html.twig' %}\n");
                    $output->writeln("{% block body %}\n");
                    $output->writeln("{% include \"component/toast.html.twig\" %}");
                    $output->writeln("{% include \"component/dialog.html.twig\" %}");
                    $output->writeln("{% include \"component/loader.html.twig\" %}\n");

                    $output->writeln("<div data-controller=\"ux\" class=\"app-bg-white w-full h-full\">");

                    $output->writeln("    {% include \"component/spacer.html.twig\" with {\"size\":80} %}");
                    $output->writeln("    {% include \"component/navbar.html.twig\" with {'selected_index':1} %}");
                    $output->writeln("");
                    $output->writeln("    <!-- FORM TWIG HERE -->");
                    $output->writeln("<fg=bright-yellow>    {% include \"form/".$_TWIG_FILENAME.".html.twig\" with {\"title\":\"Set Title\"} %}");
                    $output->writeln("<fg=bright-green>    <!-- FORM TWIG HERE -->");
                    $output->writeln("</div>");
                    $output->writeln("{% include \"component/spacer.html.twig\" with {\"size\":50} %}\n");
                    $output->writeln("{% endblock %}\n\n");
 
                    $FOLDER_NAME = str_replace("form.","",$_TWIG_FILENAME);
                    $output->writeln('<fg=bright-red>Make sure create this twig file in templates/page/<fg=bright-blue>'.$FOLDER_NAME);
                }
            }
        } catch( \Exception $e) {
            $output->writeln([
                '',
                'Error in processing file :: '.$e->getMessage()
            ]);
        }

        return Command::SUCCESS;
        
    }
}
