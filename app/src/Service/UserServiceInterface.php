<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;

/**
 * Kontrakt operacji administracyjnych na użytkownikach.
 */
interface UserServiceInterface
{
    /**
     * Zwraca listę wszystkich użytkowników (widok admina).
     *
     * @return array<User>
     */
    public function listAll(): array;

    /**
     * Aktualizuje użytkownika, pilnując inwariantów (np. nie wolno zdegradować/zablokować ostatniego admina).
     *
     * @param User        $user             użytkownik do aktualizacji
     * @param bool        $wasAdmin         czy przed zmianą posiadał ROLE_ADMIN
     * @param string|null $newPlainPassword nowe hasło (jawne) do zhashowania i ustawienia
     * @param bool|null   $blocked          flaga blokady; null = bez zmian
     *
     * @throws \LogicException Gdy naruszono inwariant (np. ostatni administrator).
     */
    public function update(User $user, bool $wasAdmin, ?string $newPlainPassword = null, ?bool $blocked = null): void;

    /**
     * Usuwa użytkownika, pilnując inwariantu „ostatni administrator”.
     *
     * @param User $user użytkownik do usunięcia
     *
     * @throws \LogicException gdy próbujesz usunąć ostatniego administratora
     */
    public function delete(User $user): void;

    /**
     * Zwraca liczbę administratorów w systemie.
     *
     * @return int liczba adminów
     */
    public function countAdmins(): int;
}
