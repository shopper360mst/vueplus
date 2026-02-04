<?php
 
namespace App\Repository;

use App\Entity\ReportEntry;
use App\Entity\CampaignConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use PDO;
/**
 * @extends ServiceEntityRepository<ReportEntry>
 */
class ReportEntryRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, private ParameterBagInterface $params, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, ReportEntry::class);
        $this->entityManager = $entityManager;
    }

    public function getParameter($value) {
        return $this->params->get($value);
    }

    /**
     * Fetch CampaignConfig for a given week number and return date range
     * 
     * @param int|null $weekNumber
     * @return array|null Returns ['start_date' => string, 'end_date' => string] or null if weekNumber is null
     * @throws \RuntimeException if CampaignConfig not found for the given week
     */
    private function getDateRangeForWeek(?int $weekNumber): ?array
    {
        if ($weekNumber === null || $weekNumber === "") {
            return null;
        }

        $campaignConfig = $this->entityManager
            ->getRepository(CampaignConfig::class)
            ->findOneBy(['week_number' => $weekNumber]);

        if (!$campaignConfig) {
            throw new \RuntimeException("CampaignConfig not found for week number: {$weekNumber}");
        }

        $startDate = $campaignConfig->getStartDate();
        $endDate = $campaignConfig->getEndDate();

        if (!$startDate || !$endDate) {
            throw new \RuntimeException("CampaignConfig for week {$weekNumber} is missing start_date or end_date");
        }

        return [
            'start_date' => $startDate->format('Y-m-d H:i:s'),
            'end_date' => $endDate->format('Y-m-d H:i:s')
        ];
    }

    public function getMainChannelEntries($channel, $weekFilter = "", $status_val = null) {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if (!$status_val) {
            if ($dateRange === null) {
                $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";
                $stmt = $conn->prepare($sql);        
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            } else {
                $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND created_date >= :start_date AND created_date < :end_date AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
                $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
            }
         
        } else {
            if ($dateRange === null) {
                $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND status = :status_val AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("status_val", $status_val, PDO::PARAM_STR);
            } else {
                $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND status = :status_val AND created_date >= :start_date AND created_date < :end_date AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("status_val", $status_val, PDO::PARAM_STR);
                $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
                $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
            }
                
        }
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative()[0]['total'];
    }

    public function getMainChannelByAgeGroupEntries($channel, $age_group, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND field4 = :age_grp AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            $stmt->bindValue("age_grp", $age_group, PDO::PARAM_STR);
        } else {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND field4 = :age_grp AND created_date >= :start_date AND created_date < :end_date AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            $stmt->bindValue("age_grp", $age_group, PDO::PARAM_STR);
            $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
            $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
        }
        $result = $stmt->executeQuery();            
        return $result->fetchAllAssociative()[0]['total'];
    }

    public function getMainChannelByErrorEntries($channel, $error, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND status='REJECTED' AND reject_reason = :reason";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            $stmt->bindValue("reason", $error, PDO::PARAM_STR);
        } else {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND status='REJECTED' AND reject_reason = :reason AND created_date >= :start_date AND created_date < :end_date";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            $stmt->bindValue("reason", $error, PDO::PARAM_STR);
            $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
            $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
        }
        $result = $stmt->executeQuery();        
        return $result->fetchAllAssociative()[0]['total'];
    }

    public function getStateEntries($state, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE state = :state AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";       
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("state", $state, PDO::PARAM_STR);
        } else {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE state = :state AND created_date >= :start_date AND created_date < :end_date AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";       
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("state", $state, PDO::PARAM_STR);
            $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
            $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
        }
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative()[0]['total'];
    }

    public function getDelivery($status, $channel, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            $sql = "SELECT 
                COUNT(1) as total 
                FROM product p  
                INNER JOIN submission s 
                ON p.id = s.product_ref 
                WHERE p.product_category = :channel
                AND p.delivery_status = :status
                ";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("status", $status, PDO::PARAM_STR);
        } else {
            if ($status == "PROCESSING") {
                $sql = "
                SELECT 
                COUNT(1) as total 
                FROM product p  
                INNER JOIN submission s 
                ON p.id = s.product_ref 
                WHERE p.product_category = :channel
                AND p.locked_date >= :start_date
   				AND p.locked_date < :end_date
                ";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
                $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
            } else {
                //DELIVERY - uses updated_date
                $sql = " 
                SELECT 
                COUNT(1) as total 
                FROM product p  
                INNER JOIN submission s 
                ON p.id = s.product_ref 
                WHERE p.product_category = :channel
                AND p.delivery_status = :status
                AND p.is_locked = 1
                AND p.updated_date >= :start_date
   				AND p.updated_date < :end_date
                ";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("status", $status, PDO::PARAM_STR);
                $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
                $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
            }
        }
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative()[0]['total'];
    }

    public function getProducts($is_redeem, $channel, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            if ($is_redeem) {
                $sql = "SELECT COUNT(1) as total 
                FROM product p  
                WHERE p.product_category = :channel AND is_locked = 1";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            } else {
                $sql = "SELECT COUNT(1) as total 
                FROM product p  
                WHERE p.product_category = :channel";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            }
        } else {
            if ($is_redeem) {
                $sql = "SELECT COUNT(1) as total 
                FROM product p  
                WHERE p.product_category = :channel 
                AND is_locked = 1
                AND locked_date >= :start_date
                AND locked_date < :end_date
                ";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
                $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
            } else {
                $sql = "SELECT COUNT(1) as total 
                FROM product p  
                WHERE p.product_category = :channel
                AND locked_date >= :start_date
                AND locked_date < :end_date
                ";
                
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
                $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
            }
        }
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative()[0]['total'];
    }

    public function getGenderEntries($gender, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE gender = :gender AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";       
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("gender", $gender, PDO::PARAM_STR);
        } else {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE gender = :gender AND created_date >= :start_date AND created_date < :end_date AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";       
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("gender", $gender, PDO::PARAM_STR);
            $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
            $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
        }

        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative()[0]['total'];
    }

    public function getGenderEntriesChannel($gender, $weekFilter = "",$channel) {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE gender = :gender AND submit_type =:channel AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";       
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("gender", $gender, PDO::PARAM_STR);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
        } else {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE gender = :gender AND created_date >= :start_date AND created_date < :end_date AND submit_type =:channel AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";       
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("gender", $gender, PDO::PARAM_STR);
            $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
            $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
        }

        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative()[0]['total'];
    }
    
    public function getReasonReject($reason, $channel, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE reject_reason = :reason AND submit_type = :channel ";       
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("reason", $reason, PDO::PARAM_STR);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
        } else {
            if($reason != 'Receipt not clear'){
                $sql = "SELECT COUNT(1) as total FROM submission WHERE reject_reason = :reason AND submit_type = :channel AND created_date >= :start_date AND created_date < :end_date AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("reason", $reason, PDO::PARAM_STR);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
                $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
            }
            else{
                $sql = "SELECT COUNT(1) as total FROM submission WHERE reject_reason is not null
                AND reject_reason NOT IN ('Duplicate receipt', 'INSUFFICIENT PURCHASE QUANTITY', 'Illegible product', 'Outside contest period',
                'You have reached the maximum number of redemptions.', 'Illegible outlet', 'TESTING')
                AND submit_type = :channel AND created_date >= :start_date AND created_date < :end_date";       
                $stmt = $conn->prepare($sql);
                $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
                $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
                $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
            }
        }

        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative()[0]['total'];
    }

    /**
     * Get entries by channel, gender, and age group combination
     *
     * @param string $channel
     * @param string $gender
     * @param string $age_group
     * @param string $weekFilter
     * @return int
     */
    public function getChannelGenderAgeEntries($channel, $gender, $age_group, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND gender = :gender AND field4 = :age_grp AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            $stmt->bindValue("gender", $gender, PDO::PARAM_STR);
            $stmt->bindValue("age_grp", $age_group, PDO::PARAM_STR);
        } else {
            $sql = "SELECT COUNT(1) as total FROM submission WHERE submit_type = :channel AND gender = :gender AND field4 = :age_grp AND created_date >= :start_date AND created_date < :end_date AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL)";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            $stmt->bindValue("gender", $gender, PDO::PARAM_STR);
            $stmt->bindValue("age_grp", $age_group, PDO::PARAM_STR);
            $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
            $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
        }
        $result = $stmt->executeQuery();            
        return $result->fetchAllAssociative()[0]['total'];
    }
}