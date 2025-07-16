<?php
namespace App\Entity;

/**
 * Class UserRole.
 */
class UserRole
{
    const ROLE_USER = 'ROLE_USER';
    const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * Get the role label.
     *
     * @param string $role The role for which the label should be returned.
     *
     * @return string The corresponding role label.
     */
    public static function label(string $role): string
    {
        // Możesz dodać dodatkowe role w tej funkcji w przyszłości
        return match ($role) {
            self::ROLE_USER => 'label.role_user',  // Klucz do tłumaczenia: ROLE_USER
            self::ROLE_ADMIN => 'label.role_admin',  // Klucz do tłumaczenia: ROLE_ADMIN
            default => 'label.role_unknown',  // Klucz dla nieznanych ról
        };
    }
}
