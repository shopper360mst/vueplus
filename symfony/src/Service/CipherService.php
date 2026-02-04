<?php 
namespace App\Service;
use App\Entity\Activity;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
/**
 *
 * A service for Activity.
 */
class CipherService {
    public $logger;
    public $em;
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }
    
	public function encrypt($message) {

        $key = $_SERVER['SALT_KEY'];
        $ivlen = openssl_cipher_iv_length("aes-256-cbc");
        $iv = substr($_SERVER['APP_SECRET'],0,$ivlen);
        $encrypted = openssl_encrypt($message,'aes-256-cbc',$key,0,$iv);
        return $encrypted;
    }

	public function decrypt($message) {
        $key = $_SERVER['SALT_KEY'];
        $ivlen = openssl_cipher_iv_length("aes-256-cbc");
        $iv = substr($_SERVER['APP_SECRET'],0,$ivlen);
        $decrypted = openssl_decrypt($message,'aes-256-cbc',$key,0,$iv);
        return $decrypted;
    }
}    
