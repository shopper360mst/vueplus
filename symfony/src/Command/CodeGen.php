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

#[AsCommand(name: 'app:code-gen', description: 'Generates controller, repository, and Vue files for a new entity')]
class CodeGen extends Command
{
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

    public function camelToSnake($camelCase) { 
        $pattern = '/(?<=\\w)(?=[A-Z])|(?<=[a-z])(?=[0-9])/'; 
        $snakeCase = preg_replace($pattern, '_', $camelCase); 
        return strtolower($snakeCase); 
    } 

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        // To get Parameter USE 
        // $inputParameterValue = ($input->getArgument('parameter'))?$input->getArgument('parameter'):"defaultValue";
        $helper = $this->getHelper('question');
        $output->writeln([
            '',
            '',
            '<fg=bright-green>###################################################### Question ######################################################',
            '',
        ]);
        $question0 = new Question('<fg=bright-yellow> What is the name of Entity in PHP e.g. ProductPlacement : ');
        $_CONTROLLER_NAME = $helper->ask($input, $output, $question0);

        if ($_CONTROLLER_NAME == "") {
            $output->writeln([
                '',
                '<fg=bright-red>ERROR:Controller name required.',
                '',
            ]);
            return Command::SUCCESS;
        }
        // $question1 = new Question('<fg=bright-yellow> Please enter desired url route (e.g. product_placement), ENTER to reuse controller as above (camel-auto): ');
        // $_ROUTE_NAME = $helper->ask($input, $output, $question1);
        $_ROUTE_NAME = "";
        $_TABLE_NAME = "";
        $_ROUTE_NAME = ($_ROUTE_NAME)?$_ROUTE_NAME:$this->camelToSnake($_CONTROLLER_NAME);
        
        // $question2 = new Question('<fg=bright-yellow> Please enter database table name (e.g. product_placement), ENTER to reuse controller as above (camel-auto) : ');        
        // $_TABLE_NAME = $helper->ask($input, $output, $question2);
        $_TABLE_NAME = ($_TABLE_NAME)?$_TABLE_NAME:$this->camelToSnake($_CONTROLLER_NAME);
        
        $questionFinal = new Question('<fg=bright-red> Overwrite PHP Controller & Repository Class Files? [Y/N] (default : N) : ');
        $_OVERWRITE = $helper->ask($input, $output, $questionFinal);
        $_OVERWRITE = ($_OVERWRITE == "")?"n":strtolower($_OVERWRITE);

        
        $output->writeln([
            '',
            '<fg=bright-green>######################################################################################################################',
            '',
        ]);
        $_FINAL_STRING = "";
        $_RP_STRING_DATA = "";
        try {
            $pathSource = $this->paramBag->get('kernel.project_dir').'//src//Command//ref//php.controller.txt';
            $pathRepo   = $this->paramBag->get('kernel.project_dir').'//src//Command//ref//php.repository.txt';
            $vueSource  = $this->paramBag->get('kernel.project_dir').'//src//Command//ref//vue.controller.txt';

            $repositoryPath  = $this->paramBag->get('kernel.project_dir').'//src//Repository//';
            $controllerPath  = $this->paramBag->get('kernel.project_dir').'//src//Controller//Admin//';
            $vueViewPath     = $this->paramBag->get('kernel.project_dir').'//vue_project//src//views//';

            $pathOutput = $this->paramBag->get('kernel.project_dir').'//src//Command//dist//php//'; 
            $pathOutputVue = $this->paramBag->get('kernel.project_dir').'//src//Command//dist//vue//';                           
            
            $_FINAL_STRING = "";
            $_FINAL_FILE = $_CONTROLLER_NAME."Controller";

            $_SRC_TEMPLATE = file_get_contents($pathSource);            
            $_STRING_DATA  = str_replace("<CONTROLLER_CLASS_NAME>", $_CONTROLLER_NAME."Controller", $_SRC_TEMPLATE);
            $_STRING_DATA  = str_replace("<ENTITY_NAME>", $_CONTROLLER_NAME, $_STRING_DATA); 
            $_STRING_DATA  = str_replace("<TITLE>", $_CONTROLLER_NAME, $_STRING_DATA);
            $_STRING_DATA  = str_replace("<ROUTE_NAME>", $_ROUTE_NAME, $_STRING_DATA);
            $_STRING_DATA  = str_replace("<TABLE_NAME>", $_TABLE_NAME, $_STRING_DATA);

            $_FINAL_STRING = $_STRING_DATA; 
            if ($_OVERWRITE != "n") {
                file_put_contents($controllerPath.$_CONTROLLER_NAME."Controller.php", $_FINAL_STRING);

            } else {
                file_put_contents($pathOutput.$_CONTROLLER_NAME."Controller.txt", $_FINAL_STRING);

            }
            
            $_SRC_REPO = file_get_contents($pathRepo);
            $_RP_STRING_DATA  = str_replace("<ENTITY_NAME>", $_CONTROLLER_NAME, $_SRC_REPO);   
            if (strtolower($_CONTROLLER_NAME) == "product") {
                $_RP_STRING_DATA  = str_replace(
                    "<PRODUCT_LOGIC>",
        "public function findAndLock(\$user, \$product_category ) {
        \$conn = \$this->getEntityManager()->getConnection();
        \$sql = 'SELECT * FROM product WHERE product_category = ? AND is_locked = 0 AND user_id IS null AND NOW() >= created_date ORDER BY id ASC LIMIT 1';
        \$stmt = \$conn->prepare(\$sql);
        \$stmt->bindValue(1, \$product_category);
        \$res = \$stmt->executeQuery();
                    
        \$freeProduct = \$res->fetchAllAssociative();
        if (count ( \$freeProduct )) {
            \$productEntity = \$this->find(\$freeProduct[0]['id']);
            \$productEntity->setUser(\$user);
            \$productEntity->setLocked(1);
                    
            // BUSINESS LOGIC
            // \$productEntity->setIsCollected(1);
            // if ( \$productEntity->getProductType() == 'PRODUCT') {
            //     \$productEntity->setDeliveryStatus('ADDRESS');
            // }
            // BUSINESS LOGIC
                    
            \$productEntity->setLockedDate(new \DateTime);
            \$this->getEntityManager()->persist(\$productEntity);
            \$this->getEntityManager()->flush();

            return \$productEntity->getId();
        } else {
            return 0;
        }
    }
        ", 
                    $_RP_STRING_DATA
                );
            } else {
                $_RP_STRING_DATA  = str_replace("<PRODUCT_LOGIC>", "", $_RP_STRING_DATA);
            }
            $_RP_STRING_DATA  = str_replace("<TABLE_NAME>", $_TABLE_NAME, $_RP_STRING_DATA);

            $_RP_FINAL_STRING = $_RP_STRING_DATA; 

            if ($_OVERWRITE != "n") {
                file_put_contents($repositoryPath.$_CONTROLLER_NAME."Repository.php", $_RP_FINAL_STRING);

            } else {
                file_put_contents($pathOutput.$_CONTROLLER_NAME."Repository.txt", $_RP_FINAL_STRING);

            }

            $_VUE_SOURCE =  file_get_contents($vueSource);
            $_VUE_STRING_DATA  = str_replace("<VUE_FILE>", $_CONTROLLER_NAME, $_VUE_SOURCE);
            $_VUE_STRING_DATA  = str_replace("<TABLE_NAME>", $_TABLE_NAME, $_VUE_STRING_DATA);

            if ($_OVERWRITE != "n") {
                file_put_contents($vueViewPath.$_CONTROLLER_NAME.".vue", $_VUE_STRING_DATA);
            } else {
                file_put_contents($pathOutputVue.$_CONTROLLER_NAME.".txt", $_VUE_STRING_DATA);
            }
            
            $output->writeln([
                '',
                '<fg=bright-blue>################################################# Output File Ready ##################################################',
                '',
            ]);
                

        } catch( \Exception $e) {
            $output->writeln([
                '',
                'Error in processing file :: '.$e->getMessage()
            ]);
        }
       
        return Command::SUCCESS;
        
    }
}
