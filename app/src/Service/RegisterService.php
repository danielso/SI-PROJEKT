<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service responsible for registering users (hashing password, assigning roles, persisting).
 *
 * Zasada architektoniczna: zapis realizuje repozytorium, nie bezpośredni EntityManager.
 */
final class RegisterService implements RegisterServiceInterface
{
    /**
     * @param UserRepository              $users  repozytorium użytkowników (persist/flush)
     * @param UserPasswordHasherInterface $hasher hasher haseł
     */
    public function __construct(private readonly UserRepository $users, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * Registers a new user: validates password, hashes it, sets roles, and persists the entity.
     *
     * @param User   $user          the user entity to register
     * @param string $plainPassword the plain (unhashed) password provided by the user
     * @param array  $roles         roles to assign if the user has no roles (defaults to ['ROLE_USER'])
     *
     * @return User the persisted user
     *
     * @throws \InvalidArgumentException when the password is empty
     */
    public function register(User $user, string $plainPassword, array $roles = ['ROLE_USER']): User
    {
        $plainPassword = trim($plainPassword);
        if ('' === $plainPassword) {
            throw new \InvalidArgumentException('Hasło nie może być puste.');
        }

        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));

        if (empty($user->getRoles())) {
            $user->setRoles($roles);
        }

        $this->users->save($user, true);

        return $user;
    }
}
