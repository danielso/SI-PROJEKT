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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ToDo::class);
    }

    /**
     * Znajdź zadania przypisane do użytkownika
     * @param $user
     * @return ToDo[]
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user') // Filtruj na podstawie użytkownika
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC') // Sortowanie po dacie utworzenia
            ->getQuery()
            ->getResult();
    }
}
