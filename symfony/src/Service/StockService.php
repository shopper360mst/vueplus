<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\Submission;
use App\Entity\PageConfig;
use App\Entity\Menu;
use App\Entity\StockAllocation;
use Doctrine\ORM\EntityManagerInterface;

class StockService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Calculate available stock based on business logic:
     * Total products not assigned to anyone minus submissions that are processing and approved but product_ref is null
     * (excluding CVSTOFT submit_type)
     */
    public function calculateAvailableStock(): int
    {
        // Get total products not assigned to anyone (user_id is null and is_locked = 0)
        $totalUnassignedProducts = $this->em->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.user IS NULL')
            ->andWhere('p.is_locked = 0')
            ->getQuery()
            ->getSingleScalarResult();

        // Get count of submissions that are processing and approved but product_ref is null
        // Excluding in-store submit_type
        $qb = $this->em->createQueryBuilder();
        $gwpCodes = ['GWP', 'SHM_AEON', 'SHM_LOTUS', 'SHM_APPEAL', 'SHM_FULL_REDEEM'];
        $pendingSubmissions = $qb->select('COUNT(s.id)')
           ->from(Submission::class, 's')
           ->where($qb->expr()->in('s.submit_code', ':gwpCodes'))
           ->andWhere('s.submit_type != :excludeSubmitType')
           ->andWhere(
               $qb->expr()->orX(
                   $qb->expr()->andX(
                       $qb->expr()->eq('s.status', ':approvedStatus'),
                       $qb->expr()->isNull('s.product_ref'),
                       $qb->expr()->isNull('s.field1')
                   ),
                   $qb->expr()->eq('s.status', ':processingStatus')
               )
           )
           ->setParameter('gwpCodes', $gwpCodes)
           ->setParameter('excludeSubmitType', 'in-store')
           ->setParameter('approvedStatus', 'approved')
           ->setParameter('processingStatus', 'processing')
           ->getQuery()
           ->getSingleScalarResult();

        return max(0, $totalUnassignedProducts - $pendingSubmissions);
    }

    /**
     * Check if products are out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->calculateAvailableStock() <= 0;
    }

    /**
     * Update the page_oss PageConfig status
     */
    public function updateOutOfStockStatus(bool $isOutOfStock): void
    {
        $pageConfig = $this->em->getRepository(PageConfig::class)->findByLabel('page_oss');
        
        if (!$pageConfig) {
            // Create new PageConfig if it doesn't exist
            $pageConfig = new PageConfig();
            $pageConfig->setLabel('page_oss');
        }
        
        $pageConfig->setStatus($isOutOfStock);
        $this->em->persist($pageConfig);
        $this->em->flush();
    }

    /**
     * Update menu publishing status for specific menu items when out of stock
     */
    public function updateMenuPublishingStatus(bool $isOutOfStock): void
    {
        // Define the menu labels that should be unpublished when out of stock
        $menuLabelsToUpdate = [
            'SuperMARKETS/hyperMarkets',
            '99 speedmart',
            'Bars, Cafes & Restaurants​',
            'e-commerce'
        ];

        foreach ($menuLabelsToUpdate as $label) {
            $menu = $this->em->getRepository(Menu::class)->findOneBy(['label' => $label]);
            if ($menu) {
                // When out of stock, set is_published to false
                // When in stock, set is_published to true
                $menu->setPublished(!$isOutOfStock);
                $this->em->persist($menu);
            }
        }
        
        $this->em->flush();
    }

    /**
     * Check stock and update PageConfig and Menu publishing status accordingly
     */
    public function checkAndUpdateStockStatus(): bool
    {
        $isOutOfStock = $this->isOutOfStock();
        $this->updateOutOfStockStatus($isOutOfStock);
        $this->updateMenuPublishingStatus($isOutOfStock);
        return $isOutOfStock;
    }

    /**
     * Get GWP Stock List from StockAllocation table
     * Returns array equivalent to the original hardcoded $GWPStockList
     */
    public function getGWPStockList(): array
    {
        $stockAllocations = $this->em->getRepository(StockAllocation::class)
            ->createQueryBuilder('sa')
            ->orderBy('sa.week_number', 'ASC')
            ->getQuery()
            ->getResult();

        $gwpStockList = [];
        foreach ($stockAllocations as $allocation) {
            $gwpStockList[] = $allocation->getStockAmount();
        }

        // Return default values if no allocations found
        if (empty($gwpStockList)) {
            return [1111, 1111, 1111, 1111, 1111, 1111, 1111, 1111, 1112];
        }

        return $gwpStockList;
    }

    /**
     * Get stock allocation for a specific week
     */
    public function getStockForWeek(int $weekNumber): int
    {
        $allocation = $this->em->getRepository(StockAllocation::class)
            ->findByWeekNumber($weekNumber);

        return $allocation ? $allocation->getStockAmount() : 1111; // Default fallback
    }

    /**
     * Update stock allocation for a specific week
     */
    public function updateStockForWeek(int $weekNumber, int $stockAmount): bool
    {
        try {
            $allocation = $this->em->getRepository(StockAllocation::class)
                ->findByWeekNumber($weekNumber);

            if (!$allocation) {
                // Create new allocation if it doesn't exist
                $allocation = new StockAllocation();
                $allocation->setWeekNumber($weekNumber);
                $allocation->setCreatedDate(new \DateTime());
            }

            $allocation->setStockAmount($stockAmount);
            $allocation->setUpdatedDate(new \DateTime());

            $this->em->persist($allocation);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get total stock across all weeks
     */
    public function getTotalAllocatedStock(): int
    {
        $result = $this->em->getRepository(StockAllocation::class)
            ->createQueryBuilder('sa')
            ->select('SUM(sa.stock_amount)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Check if stock allocation exists for all required weeks
     */
    public function hasCompleteStockAllocation(int $totalWeeks = 9): bool
    {
        $count = $this->em->getRepository(StockAllocation::class)
            ->createQueryBuilder('sa')
            ->select('COUNT(sa.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $count >= $totalWeeks;
    }
}