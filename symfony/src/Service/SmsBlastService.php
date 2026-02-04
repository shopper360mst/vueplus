<?php
namespace App\Service;
use App\Entity\BlastQueue;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class SmsBlastService
{
    public $logger;
    public $mailer;
    public $em;
    public $ts;
    public function __construct( EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger )
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->em = $em;
    }

    public function addToQueue(string $mobile_no,string $msg1 = null,string $msg2 = null,string $msg3 = null,string $msg4 = null){
        $msgQueue = new BlastQueue();
        $msgQueue->setMobileNo($mobile_no);
        $msgQueue->setMsg1($msg1);
        $msgQueue->setMsg2($msg2);
        $msgQueue->setMsg3($msg3);
        $msgQueue->setMsg4($msg4);
        $msgQueue->setStatus('PROCESSING');
        $msgQueue->setIsSent(0);
        $msgQueue->setCreatedDate(new \DateTime);
        $this->em->persist($msgQueue);
        $this->em->flush();
    }

    public function smsBlast(string $mobile_no, string $msg){
        $dt = new \DateTime();
        $dtString = $dt->format('Y-m-d');
        $msgString = urlencode($msg);
        // http://203.115.197.5:8080/GlobalSMS/routes.php?UID=shpp_app&PW=m3s0l3k1c6&MSISDN=60123000845&DCS=000&MESSAGE=Welcome
        $url="http://203.115.197.5:8080/GlobalSMS/routes.php?UID=shpp_app&PW=m3s0l3k1c6&MSISDN=";
        $query=$mobile_no."&DCS=000&MESSAGE=".$msgString;
        // $url = "https://m3tech.my:2500/imp/shopperotp/submitsm.asp";
        // $query = '?msgid='.uniqid().'&stamp='.$dtString.'&serviceid=shopperotp&amsg='.$msgString.'&mobile='.$mobile_no.'&userkey=shopperotp01&password=shopperotp001&mcn=push&msgtype=01';
        $url_final = $url.$query;
        ob_start();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_final);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $response = curl_exec($ch);

        $info = curl_getinfo($ch);
        return $response;
    }

}