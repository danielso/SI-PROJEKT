<?php
/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;

/**
 * Contract for user management operations (update, delete, admin metrics).
 */
interface UserServiceInterface
{
    /**
     * Updates a user while enforcing invariants (e.g., cannot demote/block the last admin).
     *
     * @param User        $user             The user to update.
     * @param bool        $wasAdmin         Whether the user had ROLE_ADMIN before the update.
     * @param string|null $newPlainPassword Optional new plain password to hash and set.
     * @param bool|null   $blocked          Optional blocked flag; when null, do not change.
     *
     * @return void
     *
     * @throws \LogicException If the update would violate invariants (e.g., demote/block last admin).
     */
    public function update(User $user, bool $wasAdmin, ?string $newPlainPassword = null, ?bool $blocked = null): void;

    /**
     * Deletes the given user while enforcing the "last admin" invariant.
     *
     * @param User $user The user to delete.
     *
     * @return void
     *
     * @throws \LogicException If attempting to delete the last administrator.
     */
    public function delete(User $user): void;

    /**
     * Returns the number of administrators in the system.
     *
     * @return int The number of admin users.
     */
    public function countAdmins(): int;
}
