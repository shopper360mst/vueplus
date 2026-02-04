<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Submission;
use Psr\Log\LoggerInterface;
use App\Service\MailerService;
use App\Service\ActivityService;
use App\Service\CurlToUrlService;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:sms-backup-test',
    description: 'Tests the backup SMS service by sending a test message'
)]
class SMSBackup extends Command
{
	
    public function __construct(EntityManagerInterface $entityManager, private MailerService $mailer, 
    private ParameterBagInterface $paramBag, private CurlToUrlService $cts,
    private ActivityService $avc)
    {        
        $this->entityManager = $entityManager;
		parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
       
        $headers =  array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->paramBag->get('app.backup_sms_url_key')
        );
        // $this->cts->curlToUrl($this->paramBag->get('app.diy_whatsapp_api').'get-form-field','?contest_id='.$questId,false, null, $headers);
        // $resultArr = json_decode($result);
        // contest sub full name and mobile is important
            $postData = [
                'message' => 'hiiiii',
                'mobile_no' => '60102328022'
            ];
            try{
                $RES = $this->cts->curlToUrl($this->paramBag->get('app.backup_sms_url').'sms/backup',null,true, $postData, $headers);
                $output->writeln([
                    '',
                    '<fg=bright-green> Sent Sucessfully - '.$RES
                    ]
                );
            }
            catch(\Exception $e){
                $output->writeln([
                    '',
                    '<fg=bright-red> Failed To Sent - '.$RES
                    ]
                );
            }
        return Command::SUCCESS;
    }
}
