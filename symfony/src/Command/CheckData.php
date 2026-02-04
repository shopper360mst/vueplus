<?php

namespace App\Command;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Postal;
use Symfony\Component\Console\Attribute\AsCommand;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(name: 'app:check-data', description: 'Checks database connection and displays essential configuration information')]
class CheckData extends Command
{
    // protected static $defaultName = 'app:check-data';
    private $manager;
    
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager, ParameterBagInterface $paramBag)
    {
        $this->doctrine = $registry;
        $this->param = $paramBag;
        $this->manager = $entityManager;
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {        
        $io = new SymfonyStyle($input, $output);
        try {
            $connection = $this->manager->getConnection();
            $output->writeln('');
            $output->writeln('');
            $output->writeln('');
            $output->writeln('<fg=yellow>******************* Connection Test Running : Please Verify Configuration *******************');
            $output->writeln('');
            $output->writeln('<fg=bright-yellow> DBName : '.$connection->getParams()['dbname']);
            $output->writeln('<fg=bright-yellow> Host : '.$connection->getParams()['host']);
            $output->writeln('<fg=bright-yellow> Port : '.$connection->getParams()['port']);
            $output->writeln('<fg=bright-yellow> App Secret : '.$_SERVER['APP_SECRET']);
            $output->writeln('<fg=bright-yellow> Salt Key : '.$_SERVER['SALT_KEY']);
            $output->writeln('<fg=bright-green> Mail DSN : '.$_SERVER['MAILER_DSN']);
            $output->writeln('<fg=bright-green> SMTP Sender : '.$_SERVER['SMTP_SENDER']);

            $output->writeln('<fg=bright-yellow> ');
            
            $output->writeln('<fg=bright-blue> Service Config >> app.base_url : '.$this->param->get('app.base_url'));
            $output->writeln('<fg=bright-blue> Service Config >> app.site_name : '.$this->param->get('app.site_name'));
            $output->writeln('<fg=bright-blue> Service Config >> app.version : '.$this->param->get('app.version'));

            $output->writeln('<fg=bright-blue> Service Config >> app.base_url : '.$this->param->get('app.proxy_url'));

            $output->writeln('<fg=bright-red> Service Config >> app.integration_key (for ext.party connection) : '.$this->param->get('app.integration_key'));
            $output->writeln('<fg=bright-red> Service Config >> app.google_captcha : '.$this->param->get('app.google_captcha'));
            $output->writeln('');
            $output->writeln('<fg=bright-yellow> Adhoc features is only used when necessary (to deploy features adhoc basis)');
            
            $output->writeln('<fg=bright-green> Service Config >> app.adhoc_feature1 : '.$this->param->get('app.adhoc_feature1'));
            $output->writeln('<fg=bright-green> Service Config >> app.adhoc_feature2 : '.$this->param->get('app.adhoc_feature2'));
            $output->writeln('<fg=bright-green> Service Config >> app.adhoc_feature3 : '.$this->param->get('app.adhoc_feature3'));
            
            $output->writeln('');
            $output->writeln('<fg=bright-yellow> Checking essential data...');
            
            if ($this->param->get('app.to_diy')){
                $output->writeln('<fg=bright-green> to-DIY is...: <fg=bright-red>TRUE ');
                
            } else {
                $output->writeln('<fg=bright-green> to-DIY is...: <fg=bright-red>FALSE ');
            }
            
            $_USERCOUNT     = $this->manager->getRepository(User::class);
            $_POSTALCOUNT   = $this->manager->getRepository(Postal::class);
            $_PRODUCTCOUNT  = $this->manager->getRepository(Product::class);

            $output->writeln('<fg=bright-blue> User Count    : '.$_USERCOUNT->count());
            $output->writeln('<fg=bright-blue> Postal Count  : '.$_POSTALCOUNT->count());
            $output->writeln('<fg=bright-blue> Product Count : '.$_PRODUCTCOUNT->count());
            
            $output->writeln('');
            $output->writeln('<fg=yellow>**********************************************************************************************');
            $output->writeln('');
            $output->writeln([
                '',
                'Connection command complete'
            ]);
            $output->writeln('');

        } catch( \Exception $e) {
            $output->writeln([
                '',
                'Error in insertion - '.$e->getMessage()
            ]);
        }
        
        return Command::SUCCESS;
    }
}
