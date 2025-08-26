<?php
/**
 * @license MIT
 */

namespace App\Repository;

use App\Entity\ToDo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<ToDo>
 */
class ToDoRepository extends ServiceEntityRepository
{
    /**
     * ToDoRepository constructor.
     *
     * @param ManagerRegistry $registry The registry to manage the ToDo entity.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ToDo::class);
    }

    /**
     * Builds a query listing ToDo items visible to the given user, with optional filters.
     *
     * @param User                                                                                                                $user    The user for whom we list ToDo items.
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null, scope?: 'mine'|'shared'|string|null} $filters
     *
     * @return QueryBuilder
     */
    public function queryListForUser(User $user, array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->select(
                'partial t.{id, title, content, isDone, createdAt, updatedAt, shareToken}',
                'partial c.{id, name}',
                'partial ow.{id, email}',
                'partial tag.{id, name}'
            )
            ->join('t.user', 'ow')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.tags', 'tag')
            ->leftJoin('t.collaborators', 'col')
            ->andWhere('t.user = :u OR col = :u')
            ->setParameter('u', $user)
            ->orderBy('t.updatedAt', 'DESC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->distinct();

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :cid')->setParameter('cid', (int) $filters['category']);
        }
        if (!empty($filters['tag'])) {
            $qb->andWhere('tag.id = :tid')->setParameter('tid', (int) $filters['tag']);
        }
        if (!empty($filters['search'])) {
            $s = mb_strtolower(trim((string) $filters['search']));
            if ('' !== $s) {
                $qb->andWhere('LOWER(t.title) LIKE :q OR LOWER(t.content) LIKE :q')
                    ->setParameter('q', '%'.$s.'%');
            }
        }
        if (!empty($filters['scope'])) {
            if ('mine' === $filters['scope']) {
                $qb->andWhere('t.user = :u');
            } elseif ('shared' === $filters['scope']) {
                $qb->andWhere('col = :u')->andWhere('t.user != :u');
            }
        }

        return $qb;
    }
}
