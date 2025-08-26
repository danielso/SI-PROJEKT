<?php
/**
 * @license MIT
 */

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

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
     * @param User $user
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
}
