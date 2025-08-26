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
     * @param EntityManagerInterface      $em     Entity manager used for persistence.
     * @param UserPasswordHasherInterface $hasher Password hasher for secure password updates.
     */
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * Updates the user's profile. If a new plain password is provided, it will be hashed and saved.
     *
     * @param User        $user             The user to update.
     * @param string|null $newPlainPassword Optional new plain password; when null, password remains unchanged.
     *
     * @return User The updated user entity.
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
     * @param User   $user             The user whose password will be changed.
     * @param string $newPlainPassword The new plain password to set.
     *
     * @return void
     */
    public function changePassword(User $user, string $newPlainPassword): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $newPlainPassword));
        $this->em->flush();
    }
}
