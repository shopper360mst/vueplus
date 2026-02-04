<?php 
namespace App\Service;
use App\Entity\Activity;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
/**
 *
 * A service for Activity.
 */
class ActivityService {
    public $logger;
    public $em;
    public function __construct( EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }
    
	public function setActivity($name, $request, $field1 = null, $field2 = null, $field3 = null, $uname = null) {
        if($request != null){
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $request->headers->get('user-agent');
        }
        else{
            $ip = null;
            $user_agent = null;
        }

        $actEntity = new Activity();
        $actEntity->setActivityName(strtoupper($name));
        $field1 = $field1 ?? "";
        $actEntity->setContextField1($field1);
        $actEntity->setContextField2($field2);
        $actEntity->setContextField3($field3);
        $actEntity->setUserAgent($user_agent);
        $actEntity->setUsername($uname);
        $actEntity->setIP($ip);
        $actEntity->setCreatedDate(new \DateTime());
        $this->em->persist($actEntity);
        $this->em->flush();
    }
}    
