<?php

namespace App\Repository;

use App\Entity\WinnerDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\AppBundle\DevExtremePlus\QuerySetter;
use App\Service\CipherService;

/**
 * @extends ServiceEntityRepository<WinnerDetails>
 */
class WinnerDetailsRepository extends ServiceEntityRepository
{
    private $KEY;
    private $SALT;
    private $cs;

    public function __construct(ManagerRegistry $registry, CipherService $cs)
    {
        $this->KEY = $_SERVER['APP_SECRET'];
        $this->SALT = $_SERVER['SALT_KEY'];
        $this->cs = $cs;
        parent::__construct($registry, WinnerDetails::class);
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(w)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    
    public function getCheckEntryResult($search) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT
        w.submit_code,
        w.submit_type,
        DATE_FORMAT(w.created_date, "%m/%d/%Y %r") as submitted_date,
        w.status as sub_status,
        w.field1 as limit_reached,
        w.field5 as shortlisted_winner,
        w.field7 as instore_redeem,
        w.field6 as winner_status,
        w.reject_reason as invalid_sub_reason,
        w.product_ref as product_ref,
        w.form_uuid,
        DATE_FORMAT(w.expiry_date, "%m/%d/%Y %r") as expiry_date,
        p.product_category,
        p.product_name,
        p.product_code,
        p.product_sku,
        p.expiry_date as product_expiry_date,
        p.is_locked, 
        p.locked_date, 
        p.product_photo as prize_photo,
        p.delivery_status as delivery_status,        
        p.courier_details as delivery_details,
        p.is_collected,
        DATE_FORMAT(p.collected_date, "%m/%d/%Y %r") as collection_date
        FROM winner_details w 
        LEFT JOIN product p
        ON w.product_ref = p.id 
        WHERE AES_DECRYPT(FROM_BASE64(w.mobile_no), "shopper1100", "shopperplus@cms+", "aes-256-cbc") = ?
        ORDER BY w.id DESC';
        $stmt = $conn->prepare($sql);

        /* START WITH 1 for position number rather than index */
        $stmt->bindValue(1, $search);
        $query_result = $stmt->executeQuery();
        $final_result = $query_result->fetchAllAssociative();
        return $final_result;
    }

    /**
     * Find and update winner details using DevExtreme format
     */
    public function findAndUpdate($param)
    {
        $processed = QuerySetter::GetParamsFromInput($param);
        $id = $processed['key'];
        $incomingValues = $processed['values'];

        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception('Winner details not found');
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
        if (isset($incomingValues['form_uuid'])) {
            $entity->setFormUuid($incomingValues['form_uuid']);
        }
        if (isset($incomingValues['expiry_date'])) {
            $entity->setExpiryDate(new \DateTime($incomingValues['expiry_date']));
        }

        // Update encrypted fields
        if (isset($incomingValues['full_name'])) {
            $entity->setFullName($this->cs->encrypt($incomingValues['full_name']));
        }
        if (isset($incomingValues['email'])) {
            $entity->setEmail($this->cs->encrypt($incomingValues['email']));
        }
        if (isset($incomingValues['phone'])) {
            $entity->setMobileNo($this->cs->encrypt($incomingValues['phone']));
        }
        if (isset($incomingValues['address_1'])) {
            $entity->setAddress1($this->cs->encrypt($incomingValues['address_1']));
        }
        if (isset($incomingValues['address_2'])) {
            $entity->setAddress2($this->cs->encrypt($incomingValues['address_2']));
        }
        if (isset($incomingValues['city'])) {
            $entity->setCity($this->cs->encrypt($incomingValues['city']));
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return ['success' => true, 'id' => $id];
    }

    /**
     * Find and delete winner details using DevExtreme format
     */
    public function findAndDelete($param)
    {
        $processed = QuerySetter::GetParamsFromInput($param);
        $id = $processed['key'];

        $entity = $this->find($id);
        if (!$entity) {
            throw new \Exception('Winner details not found');
        }

        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();

        return ['success' => true, 'id' => $id];
    }

    /**
     * Get export data for winner details
     */
    public function getExportData($params = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        $iv = substr($this->KEY, 0, 16); // First 16 characters of APP_SECRET for IV
        
        $sql = '
            SELECT 
                w.id,
                w.submit_code,
                w.submit_type as channel,
                AES_DECRYPT(FROM_BASE64(w.full_name), "shopper1100", "' . $iv . '", "aes-256-cbc") as full_name,
                AES_DECRYPT(FROM_BASE64(w.email), "shopper1100", "' . $iv . '", "aes-256-cbc") as email,
                AES_DECRYPT(FROM_BASE64(w.mobile_no), "shopper1100", "' . $iv . '", "aes-256-cbc") as phone,
                AES_DECRYPT(FROM_BASE64(w.address_1), "shopper1100", "' . $iv . '", "aes-256-cbc") as address_1,
                AES_DECRYPT(FROM_BASE64(w.address_2), "shopper1100", "' . $iv . '", "aes-256-cbc") as address_2,
                w.city,
                w.state,
                w.postcode,
                w.status,
                w.form_uuid,
                DATE_FORMAT(w.expiry_date, "%Y-%m-%d %H:%i:%s") as expiry_date,
                DATE_FORMAT(w.created_date, "%Y-%m-%d %H:%i:%s") as submission_date,
                COALESCE(p.product_code, w.product_ref) as product_ref
            FROM winner_details w
            LEFT JOIN product p ON p.id = w.product_ref AND (p.is_deleted IS NULL OR p.is_deleted != 1)
            ORDER BY w.created_date DESC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }

    /**
     * Find winner details by form UUID
     */
    public function findByFormUuid(string $formUuid): ?WinnerDetails
    {
        return $this->findOneBy(['form_uuid' => $formUuid]);
    }

    /**
     * Find winner details by expiry date range
     */
    public function findByExpiryDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.expiry_date >= :startDate')
            ->andWhere('w.expiry_date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('w.expiry_date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find expired winner details
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.expiry_date < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('w.expiry_date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}