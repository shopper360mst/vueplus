<?php
 
namespace App\Repository;

use App\Entity\ReportByState;
use App\Entity\CampaignConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use PDO;
/**
 * @extends ServiceEntityRepository<ReportByState>
 */
class ReportByStateRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, private ParameterBagInterface $params, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, ReportByState::class);
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
    
    public function deleteAllRelated($weekFilter) {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "DELETE FROM report_by_state WHERE week_number = :week_num";       
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue("week_num", $weekFilter, PDO::PARAM_INT);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }

    public function getStateSubmissions($state, $channel, $weekFilter = "") {
        $conn = $this->getEntityManager()->getConnection();
        $dateRange = $this->getDateRangeForWeek($weekFilter);

        if ($dateRange === null) {
            $sql = "SELECT count(id) as entries, state as state, city as city FROM submission WHERE state = :state_name AND submit_type = :channel AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL) group by city";       
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("state_name", $state, PDO::PARAM_STR);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
        } else {
            $sql = "SELECT count(id) as entries, state as state, city as city FROM submission WHERE state = :state_name AND submit_type = :channel AND created_date >= :start_date AND created_date < :end_date AND (reject_reason NOT IN ('TESTING') OR reject_reason IS NULL) group by city";                   
            $stmt = $conn->prepare($sql);
            $stmt->bindValue("state_name", $state, PDO::PARAM_STR);
            $stmt->bindValue("channel", $channel, PDO::PARAM_STR);
            $stmt->bindValue("start_date", $dateRange['start_date'], PDO::PARAM_STR);
            $stmt->bindValue("end_date", $dateRange['end_date'], PDO::PARAM_STR);
        }

        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }
}