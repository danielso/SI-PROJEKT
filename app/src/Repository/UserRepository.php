<?php

/**
 * @license MIT
 */

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * UserRepository constructor.
     *
     * @param ManagerRegistry $registry The ManagerRegistry used to access the EntityManager
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     *
     * @param PasswordAuthenticatedUserInterface $user              The user whose password is to be upgraded
     * @param string                             $newHashedPassword The new hashed password to set for the user
     *
     * @throws UnsupportedUserException if the provided user is not an instance of User
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Zapisuje (persist) encję User i opcjonalnie wykonuje flush.
     *
     * @param User $user  użytkownik do zapisania
     * @param bool $flush czy wykonać natychmiastowy flush
     */
    public function save(User $user, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($user);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Usuwa (remove) encję User i opcjonalnie wykonuje flush.
     *
     * @param User $user  użytkownik do usunięcia
     * @param bool $flush czy wykonać natychmiastowy flush
     */
    public function remove(User $user, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($user);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Counts the number of users with the 'ROLE_ADMIN'.
     *
     * @return int the number of admins
     */
    public function countAdmins(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT COUNT(*) FROM users WHERE JSON_CONTAINS(roles, :needle) = 1';

        return (int) $conn->fetchOne($sql, ['needle' => json_encode(['ROLE_ADMIN'])]);
    }
}
