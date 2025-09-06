<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\NoteRepository;
use App\Repository\ToDoRepository;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Warstwa serwisowa zarządzania użytkownikami (update, delete, licznik adminów).
 */
final class UserService implements UserServiceInterface
{
    /**
     * @param UserRepository              $users  Repozytorium użytkowników
     * @param NoteRepository              $notes  Repozytorium notatek (do liczników)
     * @param ToDoRepository              $todos  Repozytorium zadań (do liczników)
     * @param UserPasswordHasherInterface $hasher Hasher haseł
     */
    public function __construct(private readonly UserRepository $users, private readonly NoteRepository $notes, private readonly ToDoRepository $todos, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * Zwraca listę wszystkich użytkowników.
     *
     * @return array<int, User>
     */
    public function listAll(): array
    {
        return $this->users->findAll();
    }

    /**
     * Aktualizuje dane użytkownika (roles/blocked/hasło) z ochroną ostatniego administratora.
     *
     * @param User        $user             Aktualizowany użytkownik
     * @param bool        $wasAdmin         Czy przed zmianą był adminem
     * @param string|null $newPlainPassword Nowe hasło w postaci jawnej (opcjonalnie)
     * @param bool|null   $blocked          Czy zablokować użytkownika (opcjonalnie)
     *
     * @throws \LogicException Gdy próba odebrania uprawnień lub zablokowania ostatniego administratora
     */
    public function update(User $user, bool $wasAdmin, ?string $newPlainPassword = null, ?bool $blocked = null): void
    {
        $isAdminNow = $user->hasRole('ROLE_ADMIN');

        if ($wasAdmin && !$isAdminNow && $this->countAdmins() <= 1) {
            $roles   = $user->getRoles();
            $roles[] = 'ROLE_ADMIN';
            $user->setRoles(array_values(array_unique($roles)));
            throw new \LogicException('Nie można odebrać uprawnień ostatniemu administratorowi.');
        }

        if (true === $blocked && $isAdminNow && $this->countAdmins() <= 1) {
            throw new \LogicException('Nie można zablokować ostatniego administratora.');
        }

        if ($newPlainPassword) {
            $user->setPassword($this->hasher->hashPassword($user, $newPlainPassword));
        }

        if (null !== $blocked && method_exists($user, 'setBlocked')) {
            $user->setBlocked($blocked);
        }

        $this->users->save($user, true);
    }

    /**
     * Usuwa użytkownika po weryfikacji ograniczeń (ostatni admin, posiadane dane).
     *
     * @param User $user Użytkownik do usunięcia
     *
     * @throws \LogicException Gdy to ostatni administrator lub gdy posiada notatki/zadania
     */
    public function delete(User $user): void
    {
        if ($user->hasRole('ROLE_ADMIN') && $this->countAdmins() <= 1) {
            throw new \LogicException('Nie można usunąć ostatniego administratora.');
        }

        $noteCount = $this->notes->count(['user' => $user]);
        $todoCount = $this->todos->count(['user' => $user]);
        if ($noteCount > 0 || $todoCount > 0) {
            throw new \LogicException('Nie można usunąć użytkownika posiadającego notatki lub zadania.');
        }

        $this->users->remove($user, true);
    }

    /**
     * Zwraca liczbę użytkowników z rolą ROLE_ADMIN.
     *
     * @return int Liczba administratorów
     */
    public function countAdmins(): int
    {
        return $this->users->countAdmins();
    }
}
