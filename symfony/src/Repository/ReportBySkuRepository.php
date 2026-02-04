<?php

namespace App\Repository;

use App\Entity\ReportBySku;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use PDO;

/**
 * @extends ServiceEntityRepository<ReportBySku>
 */
class ReportBySkuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private ParameterBagInterface $params)
    {
        parent::__construct($registry, ReportBySku::class);
    }

    public function getParameter($value) {
        return $this->params->get($value);
    }
    
    public function deleteAllRelated($weekFilter) {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "DELETE FROM report_by_sku WHERE week_number = :week_num";       
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("week_num", $weekFilter, PDO::PARAM_INT);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }

    public function getSkuSubmissions($channel, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $liveDate = $this->getParameter('app.live_date');

        if ($weekFilter == "") {
            $sql = "SELECT field8 as sku_name, count(*) as quantity, submit_type as channel 
                    FROM submission 
                    WHERE submit_type = :channel 
                    AND field8 IS NOT NULL 
                    AND field8 != '' 
                    AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL) 
                    GROUP BY field8, submit_type";       
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);

        } else {
            $sql = "SELECT field8 as sku_name, count(*) as quantity, submit_type as channel 
                    FROM submission 
                    WHERE submit_type = :channel 
                    AND field8 IS NOT NULL 
                    AND field8 != '' 
                    AND ( WEEK( created_date,1 ) - WEEK(:live_date,1) + 1 ) = :week_num 
                    AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL) 
                    GROUP BY field8, submit_type";                   
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            $stmt->bindValue("live_date", $liveDate, PDO::PARAM_STR);            
            $stmt->bindValue("week_num", $weekFilter, PDO::PARAM_INT);
        }

        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }

    public function getAllSkuSubmissions($weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $liveDate = $this->getParameter('app.live_date');

        if ($weekFilter == "") {
            $sql = "SELECT field8 as sku_name, count(*) as quantity, submit_type as channel 
                    FROM submission 
                    WHERE field8 IS NOT NULL 
                    AND field8 != '' 
                    AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL) 
                    GROUP BY field8, submit_type";       
            $stmt = $conn->prepare($sql);

        } else {
            $sql = "SELECT field8 as sku_name, count(*) as quantity, submit_type as channel 
                    FROM submission 
                    WHERE field8 IS NOT NULL 
                    AND field8 != '' 
                    AND ( WEEK( created_date,1 ) - WEEK(:live_date,1) + 1 ) = :week_num 
                    AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL) 
                    GROUP BY field8, submit_type";                   
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("live_date", $liveDate, PDO::PARAM_STR);            
            $stmt->bindValue("week_num", $weekFilter, PDO::PARAM_INT);
        }

        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }
}