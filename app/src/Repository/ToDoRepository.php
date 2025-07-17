<?php

// src/Repository/ToDoRepository.php

namespace App\Repository;

use App\Entity\ToDo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     *
     * @param $user
     *
     * @return ToDo[]
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
