<?php

namespace App\Repository;

use App\Entity\PageConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageConfig>
 */
class PageConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageConfig::class);
    }

    //    /**
    //     * @return PageConfig[] Returns an array of PageConfig objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function findByLabel(string $label): ?PageConfig
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.label = :label')
            ->setParameter('label', $label)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}