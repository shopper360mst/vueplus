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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


#[AsCommand(name: 'app:to-diy')]
class ToDIY extends Command
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
        $this->addArgument('from', InputArgument::REQUIRED, 'FROM SUB ID required');        
        $this->addArgument('to', InputArgument::REQUIRED, 'TO SUB ID required');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate sending without making API calls');
        $this->addOption('delay', null, InputOption::VALUE_REQUIRED, 'Delay in milliseconds between each API call (default: 0)', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);
        
        $io = new SymfonyStyle($input, $output);
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');
        $dryRun = $input->getOption('dry-run');
        $delay = (int)$input->getOption('delay');
       
        if($dryRun){
            $output->writeln([
                '',
                '<fg=bright-yellow>*** DRY RUN MODE - No API calls will be made ***</fg>'
            ]);
        }

        if($delay > 0){
            $output->writeln([
                '',
                '<fg=bright-cyan>Delay: '.$delay.'ms between API calls</fg>'
            ]);
        }

        if(!$this->paramBag->get('app.to_diy')){
            $output->writeln([
                '',
                '<fg=bright-yellow>DIY Integration is disabled</fg>'
            ]);
            return Command::SUCCESS;
        }

        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->paramBag->get('app.diy_integration_key')
        );

        $total = ($to - $from) + 1;
        $processed = 0;
        $successful = 0;
        $failed = 0;

        for($i=$from;$i<($to + 1);$i++){
            $submission = $this->entityManager->getRepository(Submission::class)->findOneBy([
                'id'=>$i
            ]);
            
            $processed++;

            if(!isset($submission)){
                $output->writeln([
                    '',
                    '<fg=bright-red>['.$processed.'/'.$total.'] Submission ID '.$i.' not found</fg>'
                ]);
                $failed++;
                continue;
            }

            $output->writeln([
                '',
                '<fg=bright-green>['.$processed.'/'.$total.'] Sending Submission ID to DIY - '.$submission->getId().'</fg>'
            ]);

            $questId = $this->convertFieldtoQuest($submission->getSubmitType());
            
            try {
                $result = $this->sendToDIY(
                    $questId,
                    $submission,
                    $submission->getFullName(),
                    $submission->getMobileNo(),
                    $submission->getAttachmentNo(),
                    $submission->getEmail(),
                    $submission->getNationalId(),
                    $submission->getField10(),
                    $submission->getField1(),
                    $dryRun
                );

                if($result){
                    $output->writeln([
                        '',
                        '<fg=bright-green>✓ Sent Successfully - '.$submission->getId().'</fg>'
                    ]);
                    $successful++;
                } else {
                    $output->writeln([
                        '',
                        '<fg=bright-red>✗ Failed To Send - '.$submission->getId().'</fg>'
                    ]);
                    $failed++;
                }
            } catch(\Exception $e){
                $output->writeln([
                    '',
                    '<fg=bright-red>✗ Exception: '.$e->getMessage().' - Submission ID: '.$submission->getId().'</fg>'
                ]);
                $failed++;
            }

            if($delay > 0){
                usleep($delay * 1000);
            }
        }

        $output->writeln([
            '',
            '<fg=bright-cyan>========== SUMMARY ==========</fg>',
            '<fg=bright-cyan>Total Processed: '.$processed.'/'.$total.'</fg>',
            '<fg=bright-green>Successful: '.$successful.'</fg>',
            '<fg=bright-red>Failed: '.$failed.'</fg>',
            '<fg=bright-cyan>==============================</fg>'
        ]);

        return Command::SUCCESS;
    }

    private function sendToDIY($questId, $submission, $full_name, $mobile_no, $receipt_no, $email, $nationalId, $product=null, $state=null, $dryRun=false) {
        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->paramBag->get('app.diy_integration_key')
        );
        
        $receiptUrl = '';
        if($this->paramBag->get('app.s3_secret_key') != "") {
            $receiptUrl = $this->paramBag->get('app.s3_base_url').$this->paramBag->get('app.s3_bucket_name').'/'.$submission->getAttachment();
        } else {
            $receiptUrl = $this->paramBag->get('app.base_url').'images/uploaded/receipt/'.$submission->getAttachment();
        }
        
        $postData = array(
            'contest_id'=>$questId,
            'sub_id'=>$submission->getId(),
            'full_name'=>$full_name,
            'mobile_number'=>$mobile_no,
            'email_address'=>$email,
            'mykad'=>$nationalId,
        );
        
        if ($questId == 152) {
            $postData['receipt_no'] = $receipt_no;
            $postData['receipt'] = $receiptUrl;
            $postData['gwp'] = $product;
            $postData['state_del'] = $state;
        } else {
            $postData['receipt'] = $receiptUrl;
        }

        if($dryRun){
            return true;
        }

        try {
            $RES = $this->cts->curlToUrl($this->paramBag->get('app.diy_whatsapp_api').'submission',null,true, $postData, $headers);
            if($RES !== false && $RES !== null){
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    private function convertFieldtoQuest($channel) {
        switch($channel) {
            case "MONT":
                return $this->paramBag->get('app.integration_id1');
            break;
            case "SHM_WM":
                return $this->paramBag->get('app.integration_id2');
            break;
            case "SHM_EM":
                return $this->paramBag->get('app.integration_id2');
            break;
            case "TONT":
                return $this->paramBag->get('app.integration_id4');
            break;
            case "99SM":
                return $this->paramBag->get('app.integration_id3');
            break;
            case "ECOMM":
                return $this->paramBag->get('app.integration_id2');
            break;
            case "CVSTOFT":
                return $this->paramBag->get('app.integration_id5');
            break;
        }
    } 
}
