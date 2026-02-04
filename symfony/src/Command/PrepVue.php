<?php
namespace App\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:prep-vue', description: 'Prepares Vue.js project files for a specific entity')]
class PrepVue extends Command
{
    //protected static $defaultName = 'app:prep-vue';
    public function __construct(EntityManagerInterface $entityManager, private ParameterBagInterface $paramBag)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }
    protected function configure(): void
    {
        // e.g. php bin/console app:prep-vue C:\wwwroot\prism5_vue ClientManager clients-manager
        //$this->addArgument('vue project folder', InputArgument::REQUIRED, 'vue_project_folder');
        $this->addArgument('Entity Name', InputArgument::REQUIRED, 'vue_class');
        // $this->addArgument('route name for api', InputArgument::REQUIRED, 'route_name');
    }

    private function camelToSnake($camelCase) { 
        $result = ''; 
    
        for ($i = 0; $i < strlen($camelCase); $i++) { 
            $char = $camelCase[$i]; 
    
            if (ctype_upper($char)) { 
                $result .= '_' . strtolower($char); 
            } else { 
                $result .= $char; 
            } 
        } 
    
        return ltrim($result, '_'); 
    } 
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            //$folder = $input->getArgument('vue project folder');
            $folder = $this->paramBag->get('kernel.project_dir').'//vue_project';
            $src = $folder."//src";
            $vueClassName = $input->getArgument('Entity Name');
            $routeName = $this->camelToSnake($vueClassName);

            $myfile1 = fopen($src."//App.vue", "w");
            $contentForApp = "<template>
    <".$vueClassName."></".$vueClassName.">
</template>

<script>
    import ".$vueClassName." from './views/".$vueClassName.".vue';
    export default {
        name:\"App\",
        components: {
            ".$vueClassName."
        },
    }
</script>
<style scoped></style>
            ";
            fwrite($myfile1, $contentForApp);
            fclose($myfile1);
          
            $myfile2 = fopen($folder."//.env", "w");
            $contentForEnv2 = "VITE_ROUTE_NAME=".$routeName."
VITE_ENDPOINT=http://localhost:9988/admin/".$routeName."
VITE_BASEURL=http://localhost:9988";
            fwrite($myfile2, $contentForEnv2);
            fclose($myfile2);

            $myfile3 = fopen($folder."//.env.production", "w");
            $contentForEnv3 = "VITE_ROUTE_NAME=".$routeName."
VITE_ENDPOINT=".$routeName."
VITE_BASEURL=\n
";
            fwrite($myfile3, $contentForEnv3);
            fclose($myfile3);

            $this->outputConsole($output, 'App.vue and environment files is ready ', 'bright-yellow');

        } catch (\Exception $e) {
            $this->outputConsole($output, 'Error in executing script, might be file is locked, folder / file is invalid.', 'red');

        }
        return Command::SUCCESS;
    }

    private function outputConsole(OutputInterface $output, $msg, $color) {
        $output->writeln('<fg='.$color.'>'.$msg.'</>');
    }
}

