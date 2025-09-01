<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;

/**
 * Contract for user registration service.
 */
interface RegisterServiceInterface
{
    /**
     * Registers a new user: validates and hashes password, assigns roles, and persists the entity.
     *
     * @param User   $user          the user entity to register
     * @param string $plainPassword the plain (unhashed) password
     * @param array  $roles         roles to assign if none are set (defaults to ['ROLE_USER'])
     *
     * @return User the persisted user
     */
    public function register(User $user, string $plainPassword, array $roles = ['ROLE_USER']): User;
}
