<?php
namespace App\Service;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MailerService
{
    public $logger;
    public $mailer;
    public function __construct( MailerInterface $mailer, LoggerInterface $logger,ParameterBagInterface $params)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->params = $params;
    }
    
    public function sendEmail( $message, $emails, $senderAlias, $subject):void {            
        try {                
            $sender = $senderAlias;                
            $emailObject = (new Email())
            ->from(new Address($this->params->get('app.legit_mailer'),$sender))
            ->to(...$emails)
            ->subject($subject)
            ->html($message);

            $results = $this->mailer->send($emailObject);
            
        } catch(Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
    public function sendTwigEmail( $message, $emails, $senderAlias, $subject, $default_theme, $localhost):void {            
        try {                
            $sender = $senderAlias;
            $emailObject = (new TemplatedEmail())
                ->from(new Address($this->params->get('app.legit_mailer'),$sender))
                ->to(...$emails)
                ->subject($subject)
                ->htmlTemplate('newsletter.html.twig')
                ->context([
                    'subject' => $subject,
                    'message' => $message,
                    'footer' => "",
                    'localhost' => $localhost
                ]
            );
            
            // Log email attempt
            $this->logger->info('Attempting to send email', [
                'to' => $emails,
                'subject' => $subject,
                'from' => $this->params->get('app.legit_mailer')
            ]);
            
            $results = $this->mailer->send($emailObject);
            
            // Log successful email queue
            $this->logger->info('Email queued successfully', [
                'to' => $emails,
                'subject' => $subject,
                'message_id' => $results ? $results->getMessageId() : 'unknown'
            ]);
            
        } catch(\Exception $e) {
            $this->logger->error('Email sending failed', [
                'error' => $e->getMessage(),
                'to' => $emails ?? 'unknown',
                'subject' => $subject ?? 'unknown'
            ]);
        }
    }
    public function sendExceptionEmail($message):void {            
        try {          
            $senderAlias = $this->params->get('app.administrator_email_user');
            $emails = $this->params->get('app.incident_email');
            $siteCode = $this->params->get('app.site_code');
            $emailObject = (new Email())
            ->from(new Address($this->params->get('app.legit_mailer'),$senderAlias))
            ->to($emails)
            ->subject('Exception Caught')
            ->html("From ".$siteCode.": ".$message);
            $results = $this->mailer->send($emailObject);   
        } catch(Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}