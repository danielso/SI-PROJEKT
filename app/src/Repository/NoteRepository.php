<?php

/**
 * @license MIT
 */

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * Builds a query listing notes for the given user with optional filters.
     *
     * @param User                                                                           $user    the user whose notes to list.
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null} $filters optional filters.
     *
     * @return QueryBuilder
     */
    public function queryListForUser(User $user, array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('n')
            ->select(
                'partial n.{id, title, content, image, createdAt, updatedAt}',
                'partial c.{id, name}',
                'partial ow.{id, email}',
                'partial t.{id, name}'
            )
            ->join('n.user', 'ow')
            ->leftJoin('n.category', 'c')
            ->leftJoin('n.tags', 't')
            ->andWhere('n.user = :u')
            ->setParameter('u', $user)
            ->orderBy('n.updatedAt', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC')
            ->distinct();

        if (!empty($filters['category'])) {
            $qb->andWhere('c.id = :cid')->setParameter('cid', (int) $filters['category']);
        }

        if (!empty($filters['tag'])) {
            $qb->andWhere('t.id = :tid')->setParameter('tid', (int) $filters['tag']);
        }

        if (!empty($filters['search'])) {
            $s = mb_strtolower(trim((string) $filters['search']));
            if ('' !== $s) {
                $qb->andWhere('LOWER(n.title) LIKE :q OR LOWER(n.content) LIKE :q')
                    ->setParameter('q', '%'.$s.'%');
            }
        }

        return $qb;
    }

    /**
     * Saves Note.
     *
     * @param Note $note  notatka do zapisania.
     * @param bool $flush czy wykonać natychmiastowy flush.
     *
     * @return void
     */
    public function save(Note $note, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->persist($note);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Deletes Note.
     *
     * @param Note $note  notatka do usunięcia.
     * @param bool $flush czy wykonać natychmiastowy flush.
     *
     * @return void
     */
    public function remove(Note $note, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->remove($note);
        if ($flush) {
            $em->flush();
        }
    }
}
