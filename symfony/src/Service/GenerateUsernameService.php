<?php 
namespace App\Service;
use App\Entity\Activity;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
/**
 *
 * A service for Generate Username.
 */
class GenerateUsernameService {
    public $logger;
    public $em;
    public function __construct( EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }
    
	public function generateUsername($text) {
        return md5($text);
    }
}    
