<?php

namespace App\Repository;

use App\Entity\StockAllocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\AppBundle\Util\SQLExtraHelper;
use App\AppBundle\DevExtremePlus\QuerySetter;
use App\AppBundle\DevExtremePlus\DataSourceLoader;

class StockAllocationRepository extends ServiceEntityRepository
{
    private $TABLE_NAME = 'stock_allocation';
    private $KEY_COLUMN = 'id';

    /* NO SEMICOLON CAN BE USED FOR STARTER QUERY */
    private $STARTER_QUERY = '
        SELECT
        sa.id,
        sa.week_number,
        sa.stock_amount,
        sa.created_date,
        sa.updated_date
        FROM stock_allocation sa
        ORDER BY sa.week_number ASC
    ';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockAllocation::class);
    }

    /**
     * Find stock allocation by week number
     */
    public function findByWeekNumber(int $weekNumber): ?StockAllocation
    {
        return $this->createQueryBuilder('sa')
            ->andWhere('sa.week_number = :week_number')
            ->setParameter('week_number', $weekNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get all stock allocations for DevExtreme grid
     */
    public function getAll($params)
    {
        $conn = $this->getEntityManager()->getConnection();
        $this->dbSet = new QuerySetter($conn, $this->STARTER_QUERY, $this->TABLE_NAME);

        $result = DataSourceLoader::Load($this->dbSet, $params);
        return $result;
    }

    /**
     * Find and update stock allocation
     */
    public function findAndUpdate($params)
    {
        $processed = QuerySetter::GetParamsFromInput($params);
        $id = $processed['key'];
        $conn = $this->getEntityManager()->getConnection();
        $incomingData = $processed['values'];
        
        // Add updated_date
        $incomingData['updated_date'] = date('Y-m-d H:i:s');
        
        $setterStmts = SQLExtraHelper::convertArrayToSet($incomingData);
        $sql = "UPDATE $this->TABLE_NAME SET $setterStmts WHERE id = ?";

        $stmt = $conn->prepare($sql);
        $i = 1;
        foreach ($incomingData as $key => $value) {
            $stmt->bindValue($i, $value);
            $i++;
        }
        $stmt->bindParam(count($incomingData) + 1, $id);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    /**
     * Find and delete stock allocation
     */
    public function findAndDelete($params)
    {
        $id = QuerySetter::GetParamsFromInput($params)['key'];
        $resEnt = $this->find($id);
        $this->getEntityManager()->remove($resEnt);
        $this->getEntityManager()->flush();
        return true;
    }

    /**
     * Get total stock amount for a specific week
     */
    public function getTotalStockForWeek(int $weekNumber): int
    {
        $result = $this->createQueryBuilder('sa')
            ->select('SUM(sa.stock_amount)')
            ->andWhere('sa.week_number = :week_number')
            ->setParameter('week_number', $weekNumber)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Get stock allocations within a week range
     */
    public function findByWeekRange(int $startWeek, int $endWeek): array
    {
        return $this->createQueryBuilder('sa')
            ->andWhere('sa.week_number >= :start_week')
            ->andWhere('sa.week_number <= :end_week')
            ->setParameter('start_week', $startWeek)
            ->setParameter('end_week', $endWeek)
            ->orderBy('sa.week_number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get export data for stock allocations
     */
    public function getExportData($params = null)
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = '
            SELECT 
                sa.id,
                sa.week_number,
                sa.stock_amount,
                DATE_FORMAT(sa.created_date, "%Y-%m-%d %H:%i:%s") as created_date,
                DATE_FORMAT(sa.updated_date, "%Y-%m-%d %H:%i:%s") as updated_date
            FROM stock_allocation sa
            ORDER BY sa.week_number ASC
        ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }
}