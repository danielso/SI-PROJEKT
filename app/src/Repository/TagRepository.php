<?php
/**
 * @license MIT
 */

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * Repository class for managing Tag entities.
 */
class TagRepository extends ServiceEntityRepository
{
    /**
     * TagRepository constructor.
     *
     * @param ManagerRegistry $registry The registry to manage the Tag entity.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Returns all tags with usage counters for an user:
     *  - `todoCount`: number of ToDo items (owned by the user or where the user is a collaborator)
     *                 that reference the tag,
     *  - `noteCount`: number of the user's notes that reference the tag.
     *
     * @param User $user The user for which the counters are calculated.
     *
     * @return array<int, array{0: Tag, todoCount: int|string, noteCount: int|string}>
     */
    public function findAllWithCountsForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('tag')
            ->select('tag')
            ->addSelect(
                '(SELECT COUNT(t2.id)
                   FROM App\Entity\ToDo t2
                   JOIN t2.tags tt2
                  WHERE tt2 = tag
                    AND (t2.user = :u OR :u MEMBER OF t2.collaborators)
                ) AS todoCount'
            )
            ->addSelect(
                '(SELECT COUNT(n2.id)
                   FROM App\Entity\Note n2
                   JOIN n2.tags nt2
                  WHERE nt2 = tag
                    AND n2.user = :u
                ) AS noteCount'
            )
            ->setParameter('u', $user)
            ->orderBy('tag.name', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
