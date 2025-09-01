<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\NoteRepository;
use App\Repository\ToDoRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Warstwa serwisowa zarządzania użytkownikami (update, delete, licznik adminów).
 *
 * Zasada: serwis nie używa bezpośrednio EntityManagera — zapis/usuń realizuje repozytorium.
 */
final class UserService implements UserServiceInterface
{
    /**
     * @param UserRepository              $users  repozytorium
     *                                            użytkowników
     * @param NoteRepository              $notes  repozytorium notatek (do
     *                                            liczników)
     * @param ToDoRepository              $todos  repozytorium zadań (do
     *                                            liczników)
     * @param UserPasswordHasherInterface $hasher hasher
     *                                            haseł
     */
    public function __construct(private readonly UserRepository $users, private readonly NoteRepository $notes, private readonly ToDoRepository $todos, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    /**
     * Zwraca listę wszystkich użytkowników.
     *
     * @return User[]
     */
    public function listAll(): array
    {
        return $this->users->findAll();
    }

    /**
     * Aktualizuje dane użytkownika (roles/blocked/hasło) z ochroną ostatniego administratora.
     *
     * @param User        $user             aktualizowany użytkownik
     * @param bool        $wasAdmin         czy przed zmianą był adminem
     * @param string|null $newPlainPassword nowe hasło w postaci jawnej (opcjonalnie)
     * @param bool|null   $blocked          czy zablokować użytkownika (opcjonalnie)
     *
     * @return void
     *
     * @throws \LogicException gdy próba odebrania uprawnień lub zablokowania ostatniego administratora
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
     * @param User $user użytkownik do usunięcia
     *
     * @return void
     *
     * @throws \LogicException gdy to ostatni administrator lub gdy posiada notatki/zadania
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
     * @return int
     */
    public function countAdmins(): int
    {
        return $this->users->countAdmins();
    }
}
