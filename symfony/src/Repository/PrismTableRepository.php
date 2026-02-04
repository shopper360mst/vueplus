<?php

namespace App\Repository;

use App\Entity\PrismTable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrismTable>
 *
 * @method PrismTable|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrismTable|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrismTable[]    findAll()
 * @method PrismTable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrismTableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrismTable::class);
    }

    /**
     * Find an existing record by receipt_no, national_id, or mobile_no
     */
    public function findByReceiptOrIdentifier(?string $receiptNo, ?string $nationalId, ?string $mobileNo): ?PrismTable
    {
        $conn = $this->getEntityManager()->getConnection();
        $conditions = [];
        $params = [];

        if ($receiptNo) {
            $conditions[] = 'receipt_no = ?';
            $params[] = $receiptNo;
        }

        if ($nationalId) {
            $conditions[] = 'national_id = ?';
            $params[] = $nationalId;
        }

        if ($mobileNo) {
            $conditions[] = 'mobile_no = ?';
            $params[] = $mobileNo;
        }

        if (empty($conditions)) {
            return null;
        }

        $sql = 'SELECT id FROM prism_table WHERE ' . implode(' OR ', $conditions) . ' LIMIT 1';
        $result = $conn->executeQuery($sql, $params);
        $row = $result->fetchAssociative();

        if ($row) {
            return $this->find($row['id']);
        }

        return null;
    }
}