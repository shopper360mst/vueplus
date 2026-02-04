<?php 
namespace App\Service;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
/**
 *
 * A service for Generate Uuid Service.
 */
class GenerateUuidService {
    public $logger;
    public $em;
    public function __construct( EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }
    
	public function generateUuid(string $data = null){
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function generateUuid4() {
        $uuid = Uuid::uuid4();
        return $uuid->toString(); 
    }
}    