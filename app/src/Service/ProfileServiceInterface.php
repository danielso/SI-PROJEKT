<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;

/**
 * Contract for profile-related operations.
 */
interface ProfileServiceInterface
{
    /**
     * Updates the user's profile. If a new plain password is provided,
     * the implementation should hash and persist it.
     *
     * @param User        $user             the user to update
     * @param string|null $newPlainPassword optional new plain password; when null, password remains unchanged
     *
     * @return User the updated user entity
     */
    public function updateProfile(User $user, ?string $newPlainPassword = null): User;

    /**
     * Changes the user's password to the provided new plain password.
     *
     * @param User   $user             the user whose password will be changed
     * @param string $newPlainPassword the new plain password to set
     */
    public function changePassword(User $user, string $newPlainPassword): void;
}
