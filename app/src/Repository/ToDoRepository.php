<?php

/**
 * @license MIT
 */

namespace App\Repository;

use App\Entity\ToDo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repozytorium encji ToDo.
 *
 * @extends ServiceEntityRepository<ToDo>
 */
class ToDoRepository extends ServiceEntityRepository
{
    /**
     * Konstruktor.
     *
     * @param ManagerRegistry $registry Rejestr managerów Doctrine
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ToDo::class);
    }

    /**
     * Buduje zapytanie listujące ToDo widoczne dla danego użytkownika (własne + współdzielone).
     *
     * @param User                                                                                                                $user    Użytkownik, dla którego listujemy
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null, scope?: 'mine'|'shared'|string|null} $filters Filtry
     *
     * @return QueryBuilder QueryBuilder gotowy do wykonania/paginacji
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

    /**
     * Zapisuje encję ToDo (persist) i opcjonalnie wykonuje flush.
     *
     * @param ToDo $toDo  Encja do zapisania
     * @param bool $flush Czy wykonać natychmiastowy flush
     */
    public function save(ToDo $toDo, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->persist($toDo);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Usuwa encję ToDo i opcjonalnie wykonuje flush.
     *
     * @param ToDo $toDo  Encja do usunięcia
     * @param bool $flush Czy wykonać natychmiastowy flush
     */
    public function remove(ToDo $toDo, bool $flush = false): void
    {
        $em = $this->getEntityManager();
        $em->remove($toDo);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Znajduje ToDo po tokenie udostępniania.
     *
     * @param string $token Token udostępniania
     *
     * @return ToDo|null ToDo lub null
     */
    public function findOneByShareToken(string $token): ?ToDo
    {
        return $this->findOneBy(['shareToken' => $token]);
    }
}
