<?php 
namespace App\Service;
use App\Entity\Activity;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
/**
 * A service for PubYamlService. To be used to search public folder and retrieve information on yaml files.
 */
class PubYamlService {
    public function __construct( private EntityManagerInterface $em,  private  LoggerInterface $logger,  private ParameterBagInterface $pb)
    {        
    }
    
    public function getThemeData($specificThemeFolder) {
        $dir = $this->pb->get('kernel.project_dir') . '/public/themes/'.$specificThemeFolder.'/';
        $finder = new Finder();
        // find all files in the current directory
        $finder->files()->in($dir);
        $finder->name('manifest.yml');
        $themeManifest = [];
        foreach ($finder as $file) {
            $valueInYaml = Yaml::parseFile($file);
            array_push( $themeManifest,  $valueInYaml['manifest'] );
        }        
        return $themeManifest[0];
    }

	public function lookFolder($specificfolder) {
        try {
            $dir = $this->pb->get('kernel.project_dir') . '/public/'.$specificfolder.'/';
            $finder = new Finder();
            // find all files in the current directory
            $finder->files()->in($dir);
            $finder->name('manifest.yml');
            
            $themeManifest = [];
            foreach ($finder as $file) {
                $valueInYaml = Yaml::parseFile($file);
                
                array_push( $themeManifest,  $valueInYaml['manifest'] );
            }
        } catch( DirectoryNotFoundException $e) {
            $themeManifest = [];
        }
        return $themeManifest;
    }
}    
