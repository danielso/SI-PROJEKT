<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\ToDo;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use App\Repository\ToDoRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Serwis domenowy ToDo:
 */
final class ToDoService implements ToDoServiceInterface
{
    /**
     * Konstruktor serwisu ToDo.
     *
     * @param ToDoRepository     $toDoRepo     Repozytorium zadań ToDo
     * @param CategoryRepository $categoryRepo Repozytorium kategorii
     * @param TagRepository      $tagRepo      Repozytorium tagów
     * @param UserRepository     $userRepo     Repozytorium użytkowników
     */
    public function __construct(private readonly ToDoRepository $toDoRepo, private readonly CategoryRepository $categoryRepo, private readonly TagRepository $tagRepo, private readonly UserRepository $userRepo)
    {
    }

    /**
     * Buduje zapytanie listujące zadania użytkownika z filtrami.
     *
     * @param User                                                                                                                $user    Użytkownik, dla którego listujemy
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null, scope?: 'mine'|'shared'|string|null} $filters Filtry listy
     *
     * @return QueryBuilder Obiekt zapytania gotowy do dalszego przetwarzania
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder
    {
        return $this->toDoRepo->queryListForUser($user, $filters);
    }

    /**
     * Wyszukuje zadanie po tokenie udostępniania.
     *
     * @param string $token Token udostępniania
     *
     * @return ToDo|null Encja ToDo lub null, gdy nie znaleziono
     */
    public function findOneByShareToken(string $token): ?ToDo
    {
        return $this->toDoRepo->findOneBy(['shareToken' => $token]);
    }

    /**
     * Zwraca zadanie, jeśli należy do wskazanego właściciela.
     *
     * @param int  $id    Identyfikator zadania
     * @param User $owner Oczekiwany właściciel zadania
     *
     * @return ToDo|null Encja ToDo lub null, gdy brak uprawnień lub nie istnieje
     */
    public function findOwned(int $id, User $owner): ?ToDo
    {
        $todo = $this->toDoRepo->find($id);
        if (!$todo || $todo->getUser()?->getId() !== $owner->getId()) {
            return null;
        }

        return $todo;
    }

    /**
     * Zwraca zadanie, jeśli użytkownik może je zobaczyć (własne lub współdzielone).
     *
     * @param int  $id   Identyfikator zadania
     * @param User $user Użytkownik sprawdzający dostęp
     *
     * @return ToDo|null Encja ToDo lub null, gdy niedostępne
     */
    public function findOwnedOrShared(int $id, User $user): ?ToDo
    {
        $todo = $this->toDoRepo->find($id);
        if (!$todo) {
            return null;
        }

        return $this->canView($todo, $user) ? $todo : null;
    }

    /**
     * Tworzy zadanie wraz z kategorią i tagami.
     *
     * @param ToDo        $toDo         Nowa encja zadania
     * @param User        $owner        Właściciel zadania
     * @param string|null $categoryName Nazwa istniejącej/nowej kategorii (opcjonalnie)
     * @param string|null $tagsCsv      Nazwy tagów w formacie CSV (opcjonalnie)
     *
     * @return ToDo Zapisana encja zadania
     */
    public function create(ToDo $toDo, User $owner, ?string $categoryName, ?string $tagsCsv): ToDo
    {
        $now = new \DateTimeImmutable();
        $toDo->setUser($owner);
        $toDo->setCreatedAt($now);
        $toDo->setUpdatedAt($now);

        if (null === $toDo->getShareToken()) {
            $toDo->setShareToken(bin2hex(random_bytes(16)));
        }

        $this->applyCategoryByName($toDo, $owner, $categoryName);
        $this->applyTags($toDo, $tagsCsv);

        $this->toDoRepo->save($toDo, true);

        return $toDo;
    }

    /**
     * Aktualizuje zadanie (kategoria i tagi). Wymaga uprawnień do edycji.
     *
     * @param ToDo        $toDo         Zadanie do aktualizacji
     * @param User        $actingUser   Użytkownik wykonujący operację
     * @param string|null $categoryName Nazwa istniejącej/nowej kategorii (opcjonalnie)
     * @param string|null $tagsCsv      CSV z nazwami tagów (opcjonalnie)
     *
     * @return ToDo Zapisana encja zadania
     *
     * @throws \LogicException Gdy brak uprawnień do edycji
     */
    public function update(ToDo $toDo, User $actingUser, ?string $categoryName, ?string $tagsCsv): ToDo
    {
        $this->assertCanEdit($toDo, $actingUser);

        $toDo->setUpdatedAt(new \DateTimeImmutable());

        $this->applyCategoryByName($toDo, $toDo->getUser(), $categoryName);
        $this->applyTags($toDo, $tagsCsv);

        $this->toDoRepo->save($toDo, true);

        return $toDo;
    }

    /**
     * Usuwa zadanie. Wymaga by użytkownik był właścicielem.
     *
     * @param ToDo $toDo       Zadanie do usunięcia
     * @param User $actingUser Użytkownik wykonujący operację
     *
     * @throws \LogicException Gdy użytkownik nie jest właścicielem
     */
    public function delete(ToDo $toDo, User $actingUser): void
    {
        $this->assertOwner($toDo, $actingUser);
        $this->toDoRepo->remove($toDo, true);
    }

    /**
     * Sprawdza, czy użytkownik może zobaczyć zadanie.
     *
     * @param ToDo      $toDo Zadanie
     * @param User|null $user Użytkownik (może być null)
     *
     * @return bool True, jeśli widoczne; w przeciwnym razie false
     */
    public function canView(ToDo $toDo, ?User $user): bool
    {
        if (null === $user) {
            return false;
        }
        if ($toDo->getUser()?->getId() === $user->getId()) {
            return true;
        }

        return $toDo->getCollaborators()->exists(fn ($i, User $u) => $u->getId() === $user->getId());
    }

    /**
     * Sprawdza, czy użytkownik może edytować zadanie.
     *
     * @param ToDo      $toDo Zadanie
     * @param User|null $user Użytkownik (może być null)
     *
     * @return bool True, jeśli edytowalne; w przeciwnym razie false
     */
    public function canEdit(ToDo $toDo, ?User $user): bool
    {
        return $this->canView($toDo, $user);
    }

    /**
     * Sprawdza, czy użytkownik może usunąć zadanie (musi być właścicielem).
     *
     * @param ToDo      $toDo Zadanie
     * @param User|null $user Użytkownik (może być null)
     *
     * @return bool True, jeśli można usunąć; w przeciwnym razie false
     */
    public function canDelete(ToDo $toDo, ?User $user): bool
    {
        return (null !== $user) && ($toDo->getUser()?->getId() === $user->getId());
    }

    /**
     * Zwraca kategorie użytkownika (posortowane po nazwie).
     *
     * @param User $user Właściciel
     *
     * @return array<int, Category> Lista kategorii użytkownika
     */
    public function getCategoriesFor(User $user): array
    {
        return $this->categoryRepo->findBy(['user' => $user], ['name' => 'ASC']);
    }

    /**
     * Zwraca wszystkie tagi (posortowane po nazwie).
     *
     * @return array<int, Tag> Lista tagów
     */
    public function getAllTags(): array
    {
        return $this->tagRepo->findBy([], ['name' => 'ASC']);
    }

    /**
     * Dodaje współpracownika po adresie e-mail (tylko właściciel).
     *
     * @param ToDo   $toDo       Zadanie
     * @param string $email      Adres e-mail dodawanego użytkownika
     * @param User   $actingUser Użytkownik wykonujący
     *                           operację
     *
     * @throws \LogicException           Gdy wykonujący nie jest właścicielem
     * @throws \InvalidArgumentException Gdy e-mail jest pusty, użytkownik nie istnieje lub jest właścicielem
     */
    public function addCollaboratorByEmail(ToDo $toDo, string $email, User $actingUser): void
    {
        $this->assertOwner($toDo, $actingUser);

        $email = trim($email);
        if ('' === $email) {
            throw new \InvalidArgumentException();
        }

        $target = $this->userRepo->findOneBy(['email' => $email]);
        if (!$target) {
            throw new \InvalidArgumentException();
        }
        if ($target->getId() === $toDo->getUser()?->getId()) {
            throw new \InvalidArgumentException();
        }
        if ($toDo->getCollaborators()->contains($target)) {
            return;
        }

        $toDo->addCollaborator($target);
        $this->toDoRepo->save($toDo, true);
    }

    /**
     * Usuwa współpracownika po ID (tylko właściciel).
     *
     * @param ToDo $toDo       Zadanie
     * @param int  $userId     Identyfikator współpracownika
     * @param User $actingUser Użytkownik wykonujący operację
     *
     * @throws \LogicException Gdy wykonujący nie jest właścicielem
     */
    public function removeCollaboratorById(ToDo $toDo, int $userId, User $actingUser): void
    {
        $this->assertOwner($toDo, $actingUser);

        $target = $this->userRepo->find($userId);
        if (null !== $target && $toDo->getCollaborators()->contains($target)) {
            $toDo->removeCollaborator($target);
            $this->toDoRepo->save($toDo, true);
        }
    }

    /**
     * Przełącza status wykonania zadania (isDone).
     *
     * @param ToDo $toDo       Zadanie
     * @param User $actingUser Użytkownik wykonujący operację
     *
     * @return ToDo Zapisana encja zadania
     *
     * @throws \LogicException Gdy brak uprawnień do edycji
     */
    public function toggleDone(ToDo $toDo, User $actingUser): ToDo
    {
        $this->assertCanEdit($toDo, $actingUser);

        $toDo->setIsDone(!$toDo->isDone());
        $toDo->setUpdatedAt(new \DateTimeImmutable());

        $this->toDoRepo->save($toDo, true);

        return $toDo;
    }

    /**
     * Ustawia kategorię po nazwie.
     *
     * @param ToDo        $toDo         Zadanie
     * @param User        $owner        Właściciel zadania
     * @param string|null $categoryName Nazwa kategorii (lub null/'' aby pominąć)
     *
     * @throws \LogicException Gdy istniejąca kategoria należy do innego użytkownika
     */
    private function applyCategoryByName(ToDo $toDo, User $owner, ?string $categoryName): void
    {
        $name = trim((string) $categoryName);
        if ('' === $name) {
            return;
        }

        $existing = $this->categoryRepo->findOneBy(['user' => $owner, 'name' => $name]);
        if (null !== $existing) {
            $toDo->setCategory($existing);

            return;
        }

        $category = (new Category())
            ->setName($name)
            ->setUser($owner);

        $this->categoryRepo->save($category, false);
        $toDo->setCategory($category);
    }

    /**
     * Zastępuje tagi ToDo listą przekazaną w CSV (tworzy brakujące tagi).
     *
     * @param ToDo        $toDo    Zadanie
     * @param string|null $tagsCsv CSV z nazwami tagów
     */
    private function applyTags(ToDo $toDo, ?string $tagsCsv): void
    {
        if (null === $tagsCsv) {
            return;
        }

        foreach (clone $toDo->getTags() as $existing) {
            $toDo->removeTag($existing);
        }

        $names = array_filter(array_map('trim', explode(',', (string) $tagsCsv)));
        $owner = $toDo->getUser();

        foreach ($names as $name) {
            $tag = $this->tagRepo->findOneBy(['name' => $name, 'user' => $owner]);
            if (null === $tag) {
                $tag = (new Tag())->setName($name)->setUser($owner);
                $this->tagRepo->save($tag, false);
            }
            $toDo->addTag($tag);
        }
    }

    /**
     * Rzuca wyjątek, jeśli użytkownik nie jest właścicielem ToDo.
     *
     * @param ToDo $toDo Zadanie
     * @param User $user Użytkownik do weryfikacji
     *
     * @throws \LogicException Gdy użytkownik nie jest właścicielem zadania
     */
    private function assertOwner(ToDo $toDo, User $user): void
    {
        if ($toDo->getUser()?->getId() !== $user->getId()) {
            throw new \LogicException();
        }
    }

    /**
     * Rzuca wyjątek, jeśli użytkownik nie może edytować ToDo.
     *
     * @param ToDo $toDo Zadanie
     * @param User $user Użytkownik do weryfikacji
     *
     * @throws \LogicException Gdy brak uprawnień do edycji
     */
    private function assertCanEdit(ToDo $toDo, User $user): void
    {
        if (!$this->canEdit($toDo, $user)) {
            throw new \LogicException();
        }
    }
}
