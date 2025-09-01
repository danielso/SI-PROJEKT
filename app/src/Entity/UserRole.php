<?php

/**
 * @license MIT
 */

namespace App\Entity;

/**
 * Class UserRole.
 */
class UserRole
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * Get the role label.
     *
     * @param string $role the role for which the label should be returned
     *
     * @return string the corresponding role label
     */
    public static function label(string $role): string
    {
        return match ($role) {
            self::ROLE_USER => 'label.role_user',
            self::ROLE_ADMIN => 'label.role_admin',
            default => 'label.role_unknown',
        };
    }
}
