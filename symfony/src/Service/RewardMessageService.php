<?php
namespace App\Service;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use App\Entity\RewardMessage;
use App\Entity\Message;
use App\Service\TimeService;

class RewardMessageService
{
    public $logger;
    public $mailer;
    public $em;
    public $ts;
    public function __construct( TimeService $ts, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger )
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->ts = $ts;
        $this->em = $em;
    }

    public function sendQuestReceipt( $user, $message_content, $unique ){
        $mEntity = new Message();
        $mEntity->setUser($user);
        
        $mEntity->setMessageTitle('Quest Complete Card #'.strtoupper($unique));
        $mEntity->setMessageContent($message_content);
        $mEntity->setMessageExcerpt('This is the copy of your recent quest complete card. Click/tap to find out more.');
        $mEntity->setCreatedDate(new \DateTime);
        $mEntity->setIsGlobal(0);
        $mEntity->setMessageType('RECEIPT');
        $this->em->persist($mEntity);
        $this->em->flush();
    }

    public function sendMessageReceipt( $user, $message_content ){
        $mEntity = new Message();
        $mEntity->setUser($user);
        $uuid = uuid_create(UUID_TYPE_RANDOM);

        $mEntity->setMessageTitle('Receipt #'.strtoupper($uuid));
        $mEntity->setMessageContent($message_content);
        $mEntity->setMessageExcerpt('This is the copy of your recent redeem receipt. Click/tap to find out more.');
        $mEntity->setCreatedDate(new \DateTime);
        $mEntity->setIsGlobal(0);
        $mEntity->setMessageType('RECEIPT');
        $this->em->persist($mEntity);
        $this->em->flush();
    }

    public function sendRewardMessage ( $table, $id , $user, $unique_code, $store_item_id, $extra = "" ) {
        $rmEntity = new RewardMessage();
        $rmEntity->setTbl( $table );
        $rmEntity->setRefId( $id );
        $rmEntity->setUser( $user );
        $rmEntity->setIsDeleted( 0 );
        $rmEntity->setStoreItemIdRef( $store_item_id );
        $rmEntity->setCodeRef( $unique_code );
        $rmEntity->setIsClaimed( 0 );
        if ($extra instanceof \DateTime) {
            $result = $extra->format('Y-m-d');
        } else {
            $result = strval($extra);
        }
        $rmEntity->setExtraCaption( $result );
        $TODAY_DATE = new \DateTime();
        $MONTH_NO = $this->ts->getMonth( $TODAY_DATE );
        $YEAR_NO = $this->ts->getYear( $TODAY_DATE );
        $rmEntity->setCreatedDate( $TODAY_DATE );
        $rmEntity->setMonthNumber( $MONTH_NO );
        $rmEntity->setYearNumber( $YEAR_NO );
        $this->em->persist($rmEntity);
        $this->em->flush();
    }

}