<?php
namespace App\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'app:dx-column-gen', description: 'Generates DevExtreme column definitions from database table structure')]
class DxColumnGen extends Command
{
    // the name of the command (the part after "bin/console") example bin/console app:vue-gen <param>
    // protected static $defaultName = 'app:dx-column-gen';
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }
    protected function configure(): void
    {
    }
    
    public function camelToSnake($camelCase) { 
        $pattern = '/(?<=\\w)(?=[A-Z])|(?<=[a-z])(?=[0-9])/'; 
        $snakeCase = preg_replace($pattern, '_', $camelCase); 
        return strtolower($snakeCase); 
    } 
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $output->writeln([
            '',
            '',
            '<fg=bright-green>###################################################### Question ######################################################',
            '',
        ]);
        $question0 = new Question('<fg=bright-yellow> What is the name of Entity in PHP e.g. ProductPlacement : ');
        $entity_name = $helper->ask($input, $output, $question0);
        
        $schemaManager = $this->entityManager->getConnection()->getSchemaManager();
        $columns = array();
        try{ 
            $columns = $schemaManager->listTableColumns($this->camelToSnake($entity_name));
            $output->writeln([
                '==============================================================================',
                    $entity_name,
                '==============================================================================',
            ]);
            $columnNames = [];
            $strCol = "";
            $i = 0;
            $accumulatedTemplate = "";
            foreach($columns as $column){
                if ($column->getName() != 'id') {
                    $strCol = "<DxColumn\n";
                    $strCol .= '    data-field="'.$column->getName().'"'."\n";
                    $strCol .= '    caption="'.ucwords(str_replace("_"," ",$column->getName())).'"'."\n";
                    $strCol .= '    :allowEditing="true"'."\n";
                    $strCol .= '    :allowSorting="true"'."\n";
                    $strCol .= '    :visible="true"'."\n";
                    if ($column->getType()->getName() == "datetime"){
                    $strCol .= '    data-type="datetime"'."\n"; 
                    $strCol .= '    format="yyyy-MM-dd hh:mm:ss a"'."\n"; 
                    }
                    $camelVar = ucwords(str_replace("_"," ",$column->getName()));
                    $camelVar = str_replace(" ","", $camelVar );
                    $camelVar = lcfirst($camelVar);

                    if ( str_contains($column->getName(), 'photo') || str_contains($column->getName(), 'attachment') || str_contains($column->getName(), 'image') || str_contains($column->getName(), 'file')){
                        $strCol .= '    cell-template="'.$camelVar.'GetCT"'."\n"; 
                        $strCol .= '    edit-cell-template="'.$camelVar.'UpdateCT"'."\n"; 
                    }
                    
                    $strCol .= ">\n";                
                    if ($column->getNotnull()) {
                    $strCol .= "    <DxRequiredRule />\n";
                    }
                    if (str_contains($column->getName(), 'email')) {
                    $strCol .= "    <DxEmailRule />\n";                    
                    }
                    $strCol .= "</DxColumn>\n";

                    if ( str_contains($column->getName(), 'photo') || str_contains($column->getName(), 'attachment') || str_contains($column->getName(), 'image') || str_contains($column->getName(), 'file')){
                        $accumulatedTemplate .= "<template #".$camelVar.'GetCT'."=\"{ data }\">\n";
                        $accumulatedTemplate .= "    <template v-if=\"data.value != null\">\n";
                        $accumulatedTemplate .= "       <img :src=\"baseURL + data.value\" class=\"dx-img-upload\" @click.prevent=\"handlePopup(data.value)\">\n";
                        $accumulatedTemplate .= "    </template>\n";
                        $accumulatedTemplate .= "</template>\n";

                        $accumulatedTemplate .= "<template #".$camelVar.'UpdateCT'."=\"{ data }\">\n";
                        $accumulatedTemplate .= "    <DxFileUploader\n"; 
                        $accumulatedTemplate .= "        accept=\".png, .jpg, .jpeg\"\n";
                        $accumulatedTemplate .= "        upload-mode=\"instantly\"\n";
                        $accumulatedTemplate .= "        :multiple=\"false\"\n"; 
                        $accumulatedTemplate .= "        :upload-url=\"checkUrl(data)\"\n"; 
                        $accumulatedTemplate .= "        @progress=\"onProgress\"\n"; 
                        $accumulatedTemplate .= "        @value-changed=\"(e) => handleFileUploadValueChanged(e, data)\"\n";
                        $accumulatedTemplate .= "        @uploaded=\"(e) => handleFileUploaded(e, data)\"\n"; 
                        $accumulatedTemplate .= "        @upload-error=\"handleFileUploadError\"\n";
                        $accumulatedTemplate .= "     />\n";

                        //   <DxButton class="retryButton" text="Retry" v-model:visible="retryButtonVisible" @click="onClick"/>
                        $accumulatedTemplate .= "</template>\n";
                    }
                   
                    $output->writeln("<fg=bright-yellow>$strCol</>");
                    $columnNames[] = $column->getName();
                    $i++;
                }
            }
            
            $strFinal = "<DxColumn type=\"buttons\" :visible=\"true\" :fixed=\"false\" />\n\n";
            $strFinal .= $accumulatedTemplate;

            $output->writeln("<fg=bright-blue>$strFinal</>");
            $output->writeln([
            '',
            'Script executed in ...',
            __DIR__]); 

            $srcFolder = str_replace("\Command","",__DIR__);
            $output->writeln([
            '',
            'Entity mapped to DxColumns...',    
            $srcFolder
            ]);

            
        
        } catch(Exception $e) {
            $output->writeln($entity_name." entity doesn't exist");
        }
        return Command::SUCCESS;        
    }
}
