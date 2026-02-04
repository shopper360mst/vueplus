<?php

namespace App\Repository;

use App\Entity\Tng;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tng>
 */
class TngRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tng::class);
    }

    /**
     * Find unassigned TNGs
     */
    public function findUnassigned(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.sub_id IS NULL')
            ->andWhere('t.is_claimed = false')
            ->andWhere('t.is_locked = false')
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find TNGs assigned to submission
     */
    public function findBySubmissionId(int $subId): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.sub_id = :subId')
            ->setParameter('subId', $subId)
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Count unassigned TNGs
     */
    public function countUnassigned(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.sub_id IS NULL')
            ->andWhere('t.is_claimed = false')
            ->andWhere('t.is_locked = false')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}