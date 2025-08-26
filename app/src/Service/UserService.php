<?php
/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service layer for user management (update, delete, admin count guard).
 */
final class UserService implements UserServiceInterface
{
    /**
     * UserService constructor.
     *
     * @param EntityManagerInterface      $em     Entity manager used for persistence.
     * @param UserRepository              $users  Repository for User entities.
     * @param UserPasswordHasherInterface $hasher Password hasher for secure password updates.
     */
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserRepository $users, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * Updates a user: guards last-admin demotion/block, optionally changes password and block flag.
     *
     * @param User        $user             The user to update.
     * @param bool        $wasAdmin         Whether the user had ROLE_ADMIN before the update.
     * @param string|null $newPlainPassword Optional new plain password (hashed if provided).
     * @param bool|null   $blocked          Optional new blocked flag (if entity supports it).
     *
     * @return void
     *
     * @throws \LogicException When attempting to demote or block the last administrator.
     */
    public function update(User $user, bool $wasAdmin, ?string $newPlainPassword = null, ?bool $blocked = null): void
    {
        $isAdminNow = $user->hasRole('ROLE_ADMIN');

        if ($wasAdmin && !$isAdminNow && $this->countAdmins() <= 1) {
            $roles = $user->getRoles();
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles(array_values(array_unique($roles)));
            throw new \LogicException('Nie można odebrać uprawnień ostatniemu administratorowi.');
        }

        if (true === $blocked && $isAdminNow && $this->countAdmins() <= 1) {
            throw new \LogicException('Nie można zablokować ostatniego administratora.');
        }

        if ($newPlainPassword) {
            $user->setPassword($this->hasher->hashPassword($user, $newPlainPassword));
        }

        if (null !== $blocked && method_exists($user, 'setBlocked')) {
            $user->setBlocked($blocked);
        }

        $this->em->flush();
    }

    /**
     * Deletes a user after checking last-admin guard.
     *
     * @param User $user The user to delete.
     *
     * @return void
     *
     * @throws \LogicException When attempting to delete the last administrator.
     */
    public function delete(User $user): void
    {
        if ($user->hasRole('ROLE_ADMIN') && $this->countAdmins() <= 1) {
            throw new \LogicException('Nie można usunąć ostatniego administratora.');
        }

        $this->em->remove($user);
        $this->em->flush();
    }

    /**
     * Counts administrators (users that have ROLE_ADMIN in their roles).
     *
     * @return int Number of admin users.
     */
    public function countAdmins(): int
    {
        return $this->users->countAdmins();
    }
}
