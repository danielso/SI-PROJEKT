<?php

/**
 * @license MIT
 */

namespace App\Repository;

use App\Entity\Category;
use App\Entity\User;
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
     * @param User $user owner whose categories to list
     *
     * @return array<int, array{0: Category, todoCount: int|string, noteCount: int|string}>
     */
    public function findAllForUserWithCounts(User $user): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c')
            ->addSelect(
                '(SELECT COUNT(t2.id)
                   FROM App\Entity\ToDo t2
                  WHERE t2.category = c
                    AND (t2.user = :u OR :u MEMBER OF t2.collaborators)
                ) AS todoCount'
            )
            ->addSelect(
                '(SELECT COUNT(n2.id)
                   FROM App\Entity\Note n2
                  WHERE n2.category = c
                    AND n2.user = :u
                ) AS noteCount'
            )
            ->andWhere('c.user = :u')
            ->setParameter('u', $user)
            ->orderBy('c.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Category $category category to persist
     * @param bool     $flush    whether to flush immediately
     */
    public function save(Category $category, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->persist($category);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Usuwa (remove) encję Category i opcjonalnie wykonuje flush.
     *
     * @param Category $category kategoria do usunięcia
     * @param bool     $flush    czy wykonać natychmiastowy flush
     */
    public function remove(Category $category, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->remove($category);
        if ($flush) {
            $em->flush();
        }
    }
}
