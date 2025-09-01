<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service layer for user profile operations (profile update and password change).
 */
final class ProfileService implements ProfileServiceInterface
{
    /**
     * ProfileService constructor.
     *
     * @param EntityManagerInterface      $em     entity manager used for persistence
     * @param UserPasswordHasherInterface $hasher password hasher for secure password updates
     */
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * Updates the user's profile. If a new plain password is provided, it will be hashed and saved.
     *
     * @param User        $user             the user to update
     * @param string|null $newPlainPassword optional new plain password; when null, password remains unchanged
     *
     * @return User the updated user entity
     */
    public function updateProfile(User $user, ?string $newPlainPassword = null): User
    {
        if ($newPlainPassword) {
            $user->setPassword($this->hasher->hashPassword($user, $newPlainPassword));
        }
        $this->em->flush();

        return $user;
    }

    /**
     * Changes the user's password to the provided new plain password.
     *
     * @param User   $user             the user whose password will be changed
     * @param string $newPlainPassword the new plain password to set
     */
    public function changePassword(User $user, string $newPlainPassword): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $newPlainPassword));
        $this->em->flush();
    }
}
