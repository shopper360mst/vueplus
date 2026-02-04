<?php 
namespace App\Service;
use App\Entity\Quest;
use App\Entity\Product;
use Shuchkin\SimpleXLSX;
use App\Entity\StoreItem;
use App\Entity\Submission;
use Psr\Log\LoggerInterface;
use App\Service\CipherService;
use App\Service\MailerService;
use App\Service\SmsBlastService;
use App\Service\RewardMessageService;
use Doctrine\ORM\EntityManagerInterface;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
/**
 *
 * A service for ImportExcel.
 */
class ImportExcelService {
    public $logger;
    public $em;
    public function __construct( EntityManagerInterface $em, SmsBlastService $sms, LoggerInterface $logger, MailerService $mailer, CipherService $cs,RewardMessageService $rms, ParameterBagInterface $paramBag)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->cs = $cs;
        $this->rms = $rms;
        $this->sms = $sms;
        $this->mailer = $mailer;
        $this->paramBag = $paramBag;
    }
    
	public function importExcel($file, $entity) {
        $rowIndex = 0;
        $batchSizes = 500;
        $rowItem = [];
        // $reader = ReaderEntityFactory::createXLSXReader();
        // $reader->open($file);
        $xlsx = SimpleXLSX::parse($file);
        $this->em->getConnection()->beginTransaction();
        // $entityObj = 'App\\Entity\\'.$entity;
        switch($entity){
            case 'quest':
                foreach ( $xlsx->rows() as $row ) {
                        if ($rowIndex != 0) {                
                            $_QUEST_CODE = $row[0];
                            $_QUEST_TITLE = $row[1];
                            $_QUEST_TYPE = $row[2];
                            $_QUEST_CURR_POINTS = $row[3];
                            $_QUEST_CONTENT_CODE = $row[4];
                            $_IS_ACTIVE = $row[5];
                            $_IS_DISABLE_HS = $row[6];
                            $_QUEST_SEQUENCE = $row[7];
                            $_MASTER = $row[8];
                            $_LUCKY_NUMBER = $row[9];
                            
                            try {                      
                                $bp = new Quest();
                                $bp->setQuestCode($_QUEST_CODE);
                                $bp->setQuestTitle($_QUEST_TITLE);
                                $bp->setQuestType($_QUEST_TYPE);
                                $bp->setQuestCurrPoints($_QUEST_CURR_POINTS);
                                $bp->setQuestContentCode($_QUEST_CONTENT_CODE);
                                $bp->setIsActive($_IS_ACTIVE);
                                $bp->setIsDisableHs($_IS_DISABLE_HS);
                                $bp->setQuestSequence($_QUEST_SEQUENCE);
                                $bp->setIsMaster($_MASTER);
                                $bp->setLuckyNumber($_LUCKY_NUMBER);
    
                                $this->em->persist($bp); 
                                if (($rowIndex % $batchSizes) === 0) {    
                                    $this->em->flush();
                                    $this->em->clear();
                                }
                                $this->em->flush();
                                $this->em->clear();
                                
                            } catch (\Exception $e) {
                                $this->em->getConnection()->rollBack();                            
                                return $e->getMessage()." in Row ".$rowIndex + 1;
                                break;
                            }
                        }
                        $rowIndex++;                    
                }
                $this->em->getConnection()->commit();
                $reader->close();
                return '00000';
            break;
            case 'product':
                foreach ( $xlsx->rows() as $row ) {
                    if ($rowIndex != 0) {                
                        
                        $_PRODUCT_CODE = $row[0]->getValue();
                        $_PRODUCT_TYPE = $row[1]->getValue();
                        $_PRODUCT_EXTERNAL_CODE = $row[2]->getValue();
                        $_PRODUCT_NAME = $row[3]->getValue();
                        $_EXPIRY_DATE = $row[4]->getValue();
                        $_PUBLISHED_DATE = $row[5]->getValue();
                        $_STORE_ITEM_CODE = $row[6]->getValue();
                        
                        $matchingItem = $this->em->getRepository(StoreItem::class)->findBy(array(
                            "item_code" => $row[6]->getValue()
                        ));
                        
                        if (count($matchingItem)) {
                            $rowItem[$rowIndex] = $matchingItem;
                        } else {
                            $output->writeln([
                                '',
                                'Item Invalid at Row '.($rowIndex + 1).' ; either there is a mismatch in final column or its possible also store-item is not created prior.'

                            ]);
                            break;
                        }
                        try {                      
                            $bp = new Product();
                            $bp->setProductCode($_PRODUCT_CODE);
                            $bp->setProductType($_PRODUCT_TYPE);
                            $bp->setProductExternalCode($_PRODUCT_EXTERNAL_CODE);
                            $bp->setProductName($_PRODUCT_NAME);
                            if ($_PRODUCT_TYPE == "VOUCHER") {
                                if (isset( $_EXPIRY_DATE )) {
                                    $bp->setExpiryDate(new \DateTime($_EXPIRY_DATE));
                                }
                            }
                            if (isset( $_PUBLISHED_DATE ) ) {
                                $bp->setPublishedDate(new \DateTime($_PUBLISHED_DATE));
                            } else {
                                $bp->setPublishedDate(new \DateTime());
                            }
                            $bp->setStoreItem($rowItem[$rowIndex][0]);
                            
                            $bp->setIsLocked(0);
                            $bp->setIsClaimed(0);
                            $bp->setIsRedeemed(0);
                            $bp->setIsRevealed(0);

                            $this->em->persist($bp); 
                            if (($rowIndex % $batchSizes) === 0) {    
                                $this->em->flush();
                                $this->em->clear();
                            }
                            $this->em->flush();
                            $this->em->clear();
                        } catch (\Exception $e) {
                            $this->em->getConnection()->rollBack();                            
                            $output->writeln([
                                '',
                                $e->getMessage()." in Row (rollbacked)".$rowIndex + 1 
                            ]);
                            break;
                        }
                    }
                    $rowIndex++;                    
                }
                $this->em->getConnection()->commit();
                return '0000';
            break;
            case "cvs":
                foreach ( $xlsx->rows() as $row ) {
                    if ($rowIndex != 0) {          
                        $mobile_no = $row[0];
                        $submission = $this->em->getRepository(Submission::class)->findOneBy(
                            array(            
                                "mobile_no"=> $this->cs->encrypt($mobile_no),
                                "field_13"=> 'CVS/TOFT',
                                "field_14"=>NULL,
                                'status'=>'APPROVED'
                            )
                        );
                        if (isset($submission)) {
                            $channel = $submission->getField4();
                            $submission->setIsCvsSent(true);
                            // CVS PRIZE
                            $cvs = $this->em->getRepository(StoreItem::class)->findOneBy(
                                array(            
                                    "group_name"=> $submission->getField6()
                                )
                            );
                            try {                      
                                if ($submission->getField14() == null) {
                                    $foundAnyFreeProduct = $this->em->getRepository(Product::class)->findAndLock($submission->getUser(),$cvs);
                                    
                                    if($foundAnyFreeProduct){
                                        $this->rms->sendRewardMessage ( 
                                            $foundAnyFreeProduct->getProductType(), 
                                            $foundAnyFreeProduct->getId(), 
                                            $submission->getUser(), 
                                            $foundAnyFreeProduct->getProductExternalCode(), 
                                            $foundAnyFreeProduct->getStoreItem()->getId(),
                                            $foundAnyFreeProduct->getExpiryDate()
                                        );
                                    }
                                    $foundAnyFreeProduct->setDeliveryStatus('ADDRESS');
                                    $this->em->persist($foundAnyFreeProduct);
                                    $this->em->flush();
                                    $submission->setField14($foundAnyFreeProduct->getId());
                                    
                                    // $start_msg = "Hi ".$submission->getUser()->getFullName().",<BR><BR>";
                                    // $mid_msg = $this->paramBag->get('app.email_contest')."<BR><BR><BR>";
                                    // $end_msg = "1664 Malaysia";
    
                                    // $message = $start_msg.$mid_msg.$end_msg; 
                                    // $this->mailer->sendTwigEmail(
                                    //     $message, 
                                    //     [$submission->getUser()->getEmail()], 
                                    //     $this->paramBag->get('app.email_user'),
                                    //     $this->paramBag->get('app.email_title_contest'), 
                                    //     "", 
                                    //     false
                                    // );
                                    // $msg1 = $this->paramBag->get('app.sms_start').$this->paramBag->get('app.sms_contest');
                                    // $this->sms->addToQueue($submission->getUser()->getMobileNo(), $msg1 );
     
                                    $this->em->persist($submission);     
                                    $this->em->flush();
                                }
                                if (($rowIndex % $batchSizes) === 0) {    
                                    $this->em->flush();
                                    $this->em->clear();
                                }
                                $this->em->flush();
                                $this->em->clear();

                                $allSubmission = $this->em->getRepository(Submission::class)->findBy(
                                    array(            
                                        "field_13"=> 'CVS/TOFT',
                                        "field_14"=>NULL,
                                        "field_15"=>NULL,
                                        'status'=>'APPROVED'
                                    )
                                );
                                for($i = 0;$i<count($allSubmission);$i++){
                                    $allSubmission[$i]->setField15('DISQUALIFIED');
                                    $this->em->persist($allSubmission[$i]);     
                                    $this->em->flush();
                                }
                                $this->em->clear();
                            } catch (\Exception $e) {
                                $this->em->getConnection()->rollBack();                            
                                return $e->getMessage()." in Row ".$rowIndex + 1;
                                break;
                            }
                        }
                    }
                    $rowIndex++; 
                }
                $this->em->getConnection()->commit();
                return '0000';
            break;
            case "courier_details":
                foreach ( $xlsx->rows() as $row ) {
                    if ($rowIndex != 0) { 
                        try{
                            $id = $row[0];
                            $userFullName = $row[1];
                            $deliveryStatus = $row[5];
                            $courierDetails = $row[11];
                            
                            $matchingItem = $this->em->getRepository(Product::class)->findOneBy(array(
                                "id" => $id
                            ));
                            $matchingItem->setDeliveryStatus($deliveryStatus);
                            $matchingItem->setCourierDetails($courierDetails);
                            $this->em->persist($matchingItem);     
                            if (($rowIndex % $batchSizes) === 0) {    
                                $this->em->flush();
                                $this->em->clear();
                            }
                            $this->em->flush();
                            $this->em->clear();
                        } catch (\Exception $e) {
                            $this->em->getConnection()->rollBack();                            
                            return $e->getMessage()." in Row ".$rowIndex + 1;
                            break;
                        }
                    }
                    $rowIndex++;
                }
                $this->em->getConnection()->commit();
                return '0000';
            break;
        }
    }
}    
