<?php

// src/Repository/CategoryRepository.php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * Konstruktor klasy CategoryRepository.
     *
     * @param ManagerRegistry $registry Rejestr menedżera jednostek
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Znajdź kategorie przypisane do użytkownika
     * @param $user
     *
     * @return Category[]
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user') // Filtruj na podstawie użytkownika
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC') // Sortowanie po nazwie
            ->getQuery()
            ->getResult();
    }
}
