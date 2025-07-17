<?php

// src/Repository/NoteRepository.php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    /**
     * Konstruktor klasy NoteRepository.
     *
     * @param ManagerRegistry $registry Rejestr menedżera jednostek
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * Znajdź notatki przypisane do użytkownika
     * @param $user
     *
     * @return Note[]
     */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user') // Filtruj na podstawie użytkownika
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC') // Sortowanie po dacie utworzenia
            ->getQuery()
            ->getResult();
    }
}
