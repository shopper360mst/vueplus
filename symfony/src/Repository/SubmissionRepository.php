<?php

namespace App\Repository;

use App\Entity\Submission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\AppBundle\DevExtremePlus\QuerySetter;
use App\Service\CipherService;

/**
 * @extends ServiceEntityRepository<Submission>
 */
class SubmissionRepository extends ServiceEntityRepository
{
    private $KEY;
    private $SALT;
    private $cs;

    public function __construct(ManagerRegistry $registry, CipherService $cs)
    {
        $this->KEY = $_SERVER['APP_SECRET'];
        $this->SALT = $_SERVER['SALT_KEY'];
        $this->cs = $cs;
        parent::__construct($registry, Submission::class);
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    public function getMatchingSubmissionProductDetail($sub_id, $product_category) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT p.id as productid, 
        p.product_category, 
        p.product_name, 
        p.product_code, 
        p.delivery_status,
        p.is_locked,
        p.product_photo as prize_photo,
        p.delivery_status as delivery_status,
        p.delivery_assign as delivery_assign, 
        p.courier_details as delivery_details, 
        DATE_FORMAT(p.locked_date, "%D %b %Y") as locked_date,
        p.is_collected,
        p.courier_status, 
        p.delivered_date  FROM product p 
        WHERE p.sub_id_id = ? AND p.product_category = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $sub_id);
        $stmt->bindValue(2, $product_category);
        $query_result = $stmt->executeQuery();
        $final_result = $query_result->fetchAllAssociative();
        if ($final_result) {
            return $final_result[0];
        } else {
            return [];
        }
        
    }

    public function getAllSubmissionResult($search) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT t.*
                FROM (
                SELECT
                s.id, 
                DATE_FORMAT(s.created_date, \"%D %b %Y\") as submitted_date,
                CAST(cat.value AS UNSIGNED) AS cat_id,
                CASE 
                WHEN cat.value = '1' THEN 'LUGGAGE_SHM'
                WHEN cat.value = '2' THEN 'RUMMY_SHM'
                ELSE 'GRILL_SHM'
                END as product_category,
                CASE 
                WHEN cat.value = '1' THEN s.status1
                WHEN cat.value = '2' THEN s.status2
                ELSE s.status3 
                END as sub_status,
                CASE 
                WHEN cat.value = '1' THEN s.reason1
                WHEN cat.value = '2' THEN s.reason2
                ELSE s.reason3 
                END as reject_reason,
                s.product_ref,
                s.submit_type
                FROM
                    submission s
                CROSS JOIN
                    JSON_TABLE(
                        CONCAT('[\"', REPLACE(s.field10, ',', '\",\"'), '\"]'),
                        '$[*]' 
                        COLUMNS (
                            value VARCHAR(10) PATH '$'
                        )
                    ) AS cat
                WHERE AES_DECRYPT(FROM_BASE64(s.mobile_no), \"shopper1100\", \"shopperplus@cms+\", \"aes-256-cbc\") = ?                                
                ) as t ORDER BY t.id desc
                
                
            ";
        /*1226680335*/
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $search);
        $query_result = $stmt->executeQuery();
        $final_result = $query_result->fetchAllAssociative();
        return $final_result;
    }

    // Original query in checkentry 
    public function getCheckEntryResult($search) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT
        s.id as id,
        s.submit_code,
        s.submit_type,
        DATE_FORMAT(s.created_date, "%D %b %Y") as submitted_date,
        s.status as sub_status,
        s.field1 as limit_reached,
        s.field5 as shortlisted_winner,
        s.field7 as instore_redeem,
        s.field6 as winner_status,
        s.reject_reason as invalid_sub_reason,
        s.r_status as r_status,
        s.product_ref as product_ref,
        p.product_category,
        p.product_name,
        p.product_code,
        p.product_sku,
        p.expiry_date,
        p.is_locked,
        p.product_photo as prize_photo,
        p.delivery_status as delivery_status,
        p.delivery_assign as delivery_assign,
        GROUP_CONCAT(DISTINCT p.courier_details SEPARATOR ", ") as delivery_details,
        p.is_collected,
        DATE_FORMAT(p.locked_date, "%D %b %Y") as locked_date,
        DATE_FORMAT(s.s_validate_date, "%D %b %Y") as s_validate_date,
        DATE_FORMAT(s.r_checked_date, "%D %b %Y") as r_checked_date,
        DATE_FORMAT(p.delivered_date, "%D %b %Y") as delivered_date,
        DATE_FORMAT(p.collected_date, "%D %b %Y") as collection_date
        FROM submission s
        LEFT JOIN product p
        ON p.sub_id_id = s.id AND (p.is_deleted IS NULL OR p.is_deleted != 1)
        WHERE AES_DECRYPT(FROM_BASE64(s.mobile_no), "shopper1100", "shopperplus@cms+", "aes-256-cbc") = ?
        AND s.submit_type != "CVSTOFT"
        GROUP BY s.id
        ORDER BY s.id DESC';
        $stmt = $conn->prepare($sql);

        /* START WITH 1 for position number rather than index */
        $stmt->bindValue(1, $search);
        $query_result = $stmt->executeQuery();
        $final_result = $query_result->fetchAllAssociative();
        return $final_result;
    }

    /**
     * Find and update submission using DevExtreme format
     */
    public function findAndUpdate($param)
    {
        $processed = QuerySetter::GetParamsFromInput($param);
        $id = $processed['key'];
        $incomingValues = $processed['values'];

        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception('Submission not found');
        }

        // Update basic fields
        if (isset($incomingValues['submit_code'])) {
            $entity->setSubmitCode($incomingValues['submit_code']);
        }
        if (isset($incomingValues['channel'])) {
            $entity->setSubmitType($incomingValues['channel']);
        }
        if (isset($incomingValues['status'])) {
            $entity->setStatus($incomingValues['status']);
        }
        if (isset($incomingValues['state'])) {
            $entity->setState($incomingValues['state']);
        }
        if (isset($incomingValues['postcode'])) {
            $entity->setPostcode($incomingValues['postcode']);
        }
        if (isset($incomingValues['product_ref'])) {
            $entity->setProductRef($incomingValues['product_ref']);
        }

        // Update encrypted fields
        if (isset($incomingValues['full_name'])) {
            $entity->setFullName($incomingValues['full_name']);
        }
        if (isset($incomingValues['email'])) {
            $entity->setEmail($incomingValues['email']);
        }
        if (isset($incomingValues['phone'])) {
            $entity->setMobileNo($incomingValues['phone']);
        }
        if (isset($incomingValues['address_1'])) {
            $entity->setAddress1($incomingValues['address_1']);
        }
        if (isset($incomingValues['address_2'])) {
            $entity->setAddress2($incomingValues['address_2']);
        }
        if (isset($incomingValues['city'])) {
            $entity->setCity($incomingValues['city']);
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return ['success' => true, 'id' => $id];
    }

    /**
     * Find and delete submission using DevExtreme format
     */
    public function findAndDelete($param)
    {
        $processed = QuerySetter::GetParamsFromInput($param);
        $id = $processed['key'];

        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception('Submission not found');
        }

        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();

        return ['success' => true, 'id' => $id];
    }

    /**
     * Get export data for submissions
     */
    public function getExportData($params = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $iv = substr($this->KEY, 0, 16); // First 16 characters of APP_SECRET for IV
        
        $sql = '
            SELECT 
                s.id,
                s.submit_code,
                s.submit_type as channel,
                AES_DECRYPT(FROM_BASE64(s.full_name), "shopper1100", "' . $iv . '", "aes-256-cbc") as full_name,
                AES_DECRYPT(FROM_BASE64(s.email), "shopper1100", "' . $iv . '", "aes-256-cbc") as email,
                AES_DECRYPT(FROM_BASE64(s.mobile_no), "shopper1100", "' . $iv . '", "aes-256-cbc") as phone,
                AES_DECRYPT(FROM_BASE64(s.address_1), "shopper1100", "' . $iv . '", "aes-256-cbc") as address_1,
                AES_DECRYPT(FROM_BASE64(s.address_2), "shopper1100", "' . $iv . '", "aes-256-cbc") as address_2,
                s.city,
                s.state,
                s.postcode,
                s.status,
                DATE_FORMAT(s.created_date, "%Y-%m-%d %H:%i:%s") as submission_date,
                COALESCE(p.product_code, s.product_ref) as product_ref
            FROM submission s
            LEFT JOIN product p ON p.id = s.product_ref AND (p.is_deleted IS NULL OR p.is_deleted != 1)
            ORDER BY s.created_date DESC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }
}
