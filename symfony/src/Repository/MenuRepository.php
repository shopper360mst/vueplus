<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use App\Service\MailerService;
use App\Service\ActivityService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    public function getAllActiveParent($locale) {
        return $this->createQueryBuilder('m')
            ->andWhere('m.menu_index = 0')
            ->andWhere('m.is_published = 1')
            ->andWhere('m.locale = :locale')            
            ->setParameter('locale', $locale)            
            ->orderBy('m.weight', 'ASC')
            ->setMaxResults(25)
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function getAllSubmenuFromGroup($locale, $menuCode, $menuIndex) {
        return $this->createQueryBuilder('m')
            ->andWhere('m.menu_code = :menucode')
            ->setParameter('menucode', $menuCode)
            ->andWhere('m.menu_index = :menuindex')
            ->setParameter('menuindex', $menuIndex) 
            ->andWhere('m.locale = :locale')            
            ->setParameter('locale', $locale)            
            ->andWhere('m.is_published = 1')            
            ->orderBy('m.weight', 'ASC')
            ->setMaxResults(25)
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function getAllChildmenuFromGroup($locale, $menuCode, $subMenuCode) {
        return $this->createQueryBuilder('m')
            ->andWhere('m.menu_code = :menucode')
            ->setParameter('menucode', $menuCode)
            ->andWhere('m.submenu_code = :submenucode')
            ->setParameter('submenucode', $subMenuCode)
            ->andWhere('m.locale = :locale')            
            ->setParameter('locale', $locale)
            ->andWhere('m.menu_index = 2')
            ->andWhere('m.is_published = 1')            
            ->orderBy('m.weight', 'ASC')
            ->setMaxResults(25)
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /**
     * Fetch all active menu items for a given locale in a single query
     * This is optimized to avoid N+1 query problems
     */
    public function getAllActiveMenuItems(string $locale): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.is_published = 1')
            ->andWhere('m.locale = :locale')
            ->setParameter('locale', $locale)
            ->orderBy('m.menu_index', 'ASC')
            ->addOrderBy('m.weight', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}