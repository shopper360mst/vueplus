<?php

namespace App\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Entity\Product;
use App\Entity\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Aws\S3\S3Client;

#[AsCommand(name: 'app:s3-uploader', description: 'Uploads a default receipt file to Amazon S3 storage')]
class S3Uploader extends Command
{
    //protected static $defaultName = 'app:s3-product';
    
    public function __construct(private EntityManagerInterface $manager,private ParameterBagInterface $paramBag)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {            
            $s3 = new S3Client([
                'region'  => $this->paramBag->get('app.s3_region_name'),
                'version' => 'latest',
                'credentials' => [
                    'key'    => $this->paramBag->get('app.s3_secret_access'),
                    'secret' => $this->paramBag->get('app.s3_secret_key'),
                ]
            ]);

            $filename_label = uniqid();
            $target_file = "default-receipt.jpeg";
            $parts = explode('.', $target_file);
            $extension = end($parts);
            
            $result = $s3->putObject([
                'Bucket' => $this->paramBag->get('app.s3_bucket_name'),
                'Key'    => $filename_label.".".$extension,
                'Body'   => '',
                'SourceFile' => __DIR__."//dist//default_data//".$target_file
            ]);
            if($result) {
                $output->writeln([
                    "<fg=bright-green>Upload successful..."
                ]);
            }

        } catch( \Exception $e) {
            $output->writeln([
                '',
                '<fg=bright-red>Oops something wrong in uploader >>> '.$e->getMessage()
            ]);
            $output->writeln([
                '',
                $e->getMessage()
            ]);
        }
        
        return Command::SUCCESS;
        
    }
}
