<?php
/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service responsible for registering users (hashing password, assigning roles, persisting).
 */
final class RegisterService implements RegisterServiceInterface
{
    /**
     * RegisterService constructor.
     *
     * @param EntityManagerInterface      $em     Entity manager used to persist users.
     * @param UserPasswordHasherInterface $hasher Password hasher for secure password storage.
     */
    public function __construct(private readonly EntityManagerInterface $em, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * Registers a new user: validates password, hashes it, sets roles, and persists the entity.
     *
     * @param User   $user          The user entity to register.
     * @param string $plainPassword The plain (unhashed) password provided by the user.
     * @param array  $roles         Roles to assign if the user has no roles (defaults to ['ROLE_USER']).
     *
     * @return User The persisted user.
     *
     * @throws \InvalidArgumentException When the password is empty.
     */
    public function register(User $user, string $plainPassword, array $roles = ['ROLE_USER']): User
    {
        $plainPassword = trim($plainPassword);
        if ('' === $plainPassword) {
            throw new \InvalidArgumentException('Hasło nie może być puste.');
        }

        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
        if (!$user->getRoles() || ['ROLE_USER'] === $user->getRoles()) {
            $user->setRoles($roles);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
