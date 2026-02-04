<?php
namespace App\Service;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

class CurlToUrlService
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

    public function curlToUrl(string $url, $query, bool $isPost, $postData = null, $headers = null){
        try{
            if(!$isPost){
                $url_final = $url.$query;
                ob_start();
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url_final);
                if($headers != null){
                    $header = $headers;
                }
                else{
                    $header = array();
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                $response = curl_exec($ch);
                return $response;
            }
            else{
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                if($postData != null){
                    $data = $postData;
                }
                else{
                    $data = array();
                }
                if($headers != null){
                    $header = $headers;
                }
                else{
                    $header = array();
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                $response = curl_exec($ch);
                if ($response === false) {
                    $response = curl_error($ch);
                }
                return $response;
            }
        }
        catch(\Exception $e){
            return $e->getMessage();
        }
    }
}