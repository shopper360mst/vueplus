<?php

namespace App\Repository;

use App\Entity\ProcessingState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProcessingState>
 */
class ProcessingStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProcessingState::class);
    }

    public function getLastProcessedCategory(string $processName): ?string
    {
        $state = $this->findOneBy(['process_name' => $processName]);
        return $state ? $state->getLastCategory() : null;
    }

    public function updateLastProcessedCategory(string $processName, string $category): void
    {
        $state = $this->findOneBy(['process_name' => $processName]);
        
        if (!$state) {
            $state = new ProcessingState();
            $state->setProcessName($processName);
        }
        
        $state->setLastCategory($category);
        $state->setUpdatedDate(new \DateTime());
        
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();
    }
}