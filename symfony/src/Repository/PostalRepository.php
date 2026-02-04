<?php

namespace App\Repository;

use App\Entity\Postal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Postal>
 */
class PostalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Postal::class);
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getAllPostal($filter)
    {
        $RESULTS = null;
        $conn = $this->getEntityManager()->getConnection();
        if($filter == 'em'){
            $sql = 'SELECT
            CONCAT(postcode," , ",city," , ",state) AS label,
            CONCAT(postcode,",",city,",",state) as value,
            city,
            postcode,
            state
            FROM postal WHERE state in ("Sabah","Sarawak","Labuan") ORDER BY id ASC';
            $stmt = $conn->prepare($sql);
            // $stmt->bindValue(1, $filter);
            $res = $stmt->executeQuery();
            $RESULTS = $res->fetchAllAssociative();
        }
        else{
            $sql = 'SELECT 
            CONCAT(postcode," , ",city," , ",state) AS label,
            CONCAT(postcode,",",city,",",state) as value,
            city,
            postcode,
            state
            FROM postal ORDER BY id ASC';
            $stmt = $conn->prepare($sql);
            $res = $stmt->executeQuery();
            $RESULTS = $res->fetchAllAssociative();
        }
        return $RESULTS;
    }

    //    /**
    //     * @return Postal[] Returns an array of Postal objects
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

    //    public function findOneBySomeField($value): ?Postal
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
