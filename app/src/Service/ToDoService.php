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
 * listowanie, CRUD, uprawnienia, kategorie/tagi, współpracownicy.
 *
 * Zasada: brak bezpośredniego użycia EntityManager – zapis/usuń realizują repozytoria.
 */
final class ToDoService implements ToDoServiceInterface
{
    /**
     * Konstruktor serwisu ToDo.
     *
     * @param ToDoRepository     $toDoRepo     repozytorium ToDo
     * @param CategoryRepository $categoryRepo repozytorium kategorii
     * @param TagRepository      $tagRepo      repozytorium tagów
     * @param UserRepository     $userRepo     repozytorium użytkowników
     */
    public function __construct(private readonly ToDoRepository $toDoRepo, private readonly CategoryRepository $categoryRepo, private readonly TagRepository $tagRepo, private readonly UserRepository $userRepo)
    {
    }

    /**
     * Buduje zapytanie listujące zadania użytkownika z filtrami.
     *
     * @param User                                                                                                                $user    użytkownik
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null, scope?: 'mine'|'shared'|string|null} $filters filtry
     *
     * @return QueryBuilder
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder
    {
        return $this->toDoRepo->queryListForUser($user, $filters);
    }

    /**
     * Wyszukuje zadanie po tokenie udostępniania.
     *
     * @param string $token token udostępniania.
     *
     * @return ToDo|null
     */
    public function findOneByShareToken(string $token): ?ToDo
    {
        return $this->toDoRepo->findOneBy(['shareToken' => $token]);
    }

    /**
     * Zwraca zadanie, jeśli należy do wskazanego właściciela.
     *
     * @param int  $id    identyfikator zadania.
     * @param User $owner oczekiwany właściciel.
     *
     * @return ToDo|null
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
     * @param int  $id   identyfikator zadania.
     * @param User $user użytkownik sprawdzający dostęp.
     *
     * @return ToDo|null
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
     * @param ToDo        $toDo            nowa encja.
     * @param User        $owner           właściciel.
     * @param int|null    $categoryId      ID istniejącej kategorii.
     *                                     (opcjonalnie).
     * @param string|null $newCategoryName nazwa nowej kategorii (opcjonalnie).
     * @param string|null $tagsCsv         CSV z nazwami tagów.
     *                                     (opcjonalnie).
     *
     * @return ToDo
     */
    public function create(ToDo $toDo, User $owner, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv): ToDo
    {
        $now = new \DateTimeImmutable();
        $toDo->setUser($owner);
        $toDo->setCreatedAt($now);
        $toDo->setUpdatedAt($now);

        if (null === $toDo->getShareToken()) {
            $toDo->setShareToken(bin2hex(random_bytes(16)));
        }

        $this->applyCategory($toDo, $owner, $categoryId, $newCategoryName);
        $this->applyTags($toDo, $tagsCsv);

        $this->toDoRepo->save($toDo, true);

        return $toDo;
    }

    /**
     * Aktualizuje zadanie (kategoria i tagi). Wymaga uprawnień do edycji.
     *
     * @param ToDo        $toDo            zadanie do aktualizacji.
     * @param User        $actingUser      użytkownik wykonujący.
     *                                     operację.
     * @param int|null    $categoryId      ID istniejącej kategorii.
     *                                     (opcjonalnie).
     * @param string|null $newCategoryName nazwa nowej kategorii (opcjonalnie).
     * @param string|null $tagsCsv         CSV z nazwami tagów.
     *                                     (opcjonalnie).
     *
     * @return ToDo
     *
     * @throws \LogicException gdy brak uprawnień do edycji
     */
    public function update(ToDo $toDo, User $actingUser, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv): ToDo
    {
        $this->assertCanEdit($toDo, $actingUser);

        $toDo->setUpdatedAt(new \DateTimeImmutable());
        $this->applyCategory($toDo, $toDo->getUser(), $categoryId, $newCategoryName);
        $this->applyTags($toDo, $tagsCsv);

        $this->toDoRepo->save($toDo, true);

        return $toDo;
    }

    /**
     * Usuwa zadanie. Wymaga by użytkownik był właścicielem.
     *
     * @param ToDo $toDo       zadanie do usunięcia.
     * @param User $actingUser użytkownik wykonujący operację.
     *
     * @return void
     *
     * @throws \LogicException gdy użytkownik nie jest właścicielem
     */
    public function delete(ToDo $toDo, User $actingUser): void
    {
        $this->assertOwner($toDo, $actingUser);
        $this->toDoRepo->remove($toDo, true);
    }

    /**
     * Sprawdza, czy użytkownik może zobaczyć zadanie.
     *
     * @param ToDo      $toDo zadanie
     * @param User|null $user użytkownik (może być null)
     *
     * @return bool
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
     * @param ToDo      $toDo zadanie
     * @param User|null $user użytkownik (może być null)
     *
     * @return bool
     */
    public function canEdit(ToDo $toDo, ?User $user): bool
    {
        return $this->canView($toDo, $user);
    }

    /**
     * Sprawdza, czy użytkownik może usunąć zadanie (musi być właścicielem).
     *
     * @param ToDo      $toDo zadanie
     * @param User|null $user użytkownik (może być null)
     *
     * @return bool
     */
    public function canDelete(ToDo $toDo, ?User $user): bool
    {
        return (null !== $user) && ($toDo->getUser()?->getId() === $user->getId());
    }

    /**
     * Zwraca kategorie użytkownika (posortowane po nazwie).
     *
     * @param User $user właściciel
     *
     * @return array<Category>
     */
    public function getCategoriesFor(User $user): array
    {
        return $this->categoryRepo->findBy(['user' => $user], ['name' => 'ASC']);
    }

    /**
     * Zwraca wszystkie tagi (posortowane po nazwie).
     *
     * @return array<Tag>
     */
    public function getAllTags(): array
    {
        return $this->tagRepo->findBy([], ['name' => 'ASC']);
    }

    /**
     * Dodaje współpracownika po adresie e-mail (tylko właściciel).
     *
     * @param ToDo   $toDo       zadanie
     * @param string $email      adres e-mail dodawanego
     *                           użytkownika
     * @param User   $actingUser użytkownik wykonujący
     *                           operację
     *
     * @return void
     *
     * @throws \LogicException          gdy wykonujący nie jest właścicielem
     * @throws \InvalidArgumentException gdy e-mail pusty lub użytkownik nie istnieje / jest właścicielem
     */
    public function addCollaboratorByEmail(ToDo $toDo, string $email, User $actingUser): void
    {
        $this->assertOwner($toDo, $actingUser);

        $email = trim($email);
        if ('' === $email) {
            throw new \InvalidArgumentException('E-mail jest wymagany.');
        }

        $target = $this->userRepo->findOneBy(['email' => $email]);
        if (!$target) {
            throw new \InvalidArgumentException('Nie znaleziono użytkownika o podanym e-mailu.');
        }
        if ($target->getId() === $toDo->getUser()?->getId()) {
            throw new \InvalidArgumentException('Właściciel nie może być własnym współpracownikiem.');
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
     * @param ToDo $toDo       zadanie
     * @param int  $userId     identyfikator współpracownika
     * @param User $actingUser użytkownik wykonujący operację
     *
     * @return void
     *
     * @throws \LogicException gdy wykonujący nie jest właścicielem
     */
    public function removeCollaboratorById(ToDo $toDo, int $userId, User $actingUser): void
    {
        $this->assertOwner($toDo, $actingUser);

        $target = $this->userRepo->find($userId);
        if ($target && $toDo->getCollaborators()->contains($target)) {
            $toDo->removeCollaborator($target);
            $this->toDoRepo->save($toDo, true);
        }
    }

    /**
     * Przełącza status wykonania zadania (isDone).
     *
     * @param ToDo $toDo       zadanie
     * @param User $actingUser użytkownik wykonujący operację
     *
     * @return ToDo
     *
     * @throws \LogicException gdy brak uprawnień do edycji
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
     * Ustawia kategorię istniejącą po ID lub tworzy nową po nazwie.
     *
     * @param ToDo        $toDo            zadanie
     * @param User        $owner           właściciel zadania
     * @param int|null    $categoryId      ID istniejącej kategorii
     * @param string|null $newCategoryName nazwa nowej kategorii
     *
     * @return void
     *
     * @throws \LogicException gdy istniejąca kategoria należy do innego użytkownika
     */
    private function applyCategory(ToDo $toDo, User $owner, ?int $categoryId, ?string $newCategoryName): void
    {
        $newCategoryName = $newCategoryName ? trim($newCategoryName) : null;

        if ($categoryId) {
            $category = $this->categoryRepo->find((int) $categoryId);
            if ($category && $category->getUser()?->getId() === $owner->getId()) {
                $toDo->setCategory($category);

                return;
            }
            if ($category) {
                throw new \LogicException('Wybrana kategoria nie należy do użytkownika.');
            }
        }

        if ($newCategoryName) {
            $category = (new Category())
                ->setName($newCategoryName)
                ->setUser($owner);
            // zapis bez flush; finalny flush wykona zapis ToDo
            $this->categoryRepo->save($category, false);
            $toDo->setCategory($category);
        }
    }

    /**
     * Zastępuje tagi ToDo listą przekazaną w CSV (tworzy brakujące tagi).
     *
     * @param ToDo        $toDo    zadanie
     * @param string|null $tagsCsv CSV z nazwami tagów
     *
     * @return void
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
        foreach ($names as $name) {
            $tag = $this->tagRepo->findOneBy(['name' => $name]);
            if (!$tag) {
                $tag = (new Tag())->setName($name);
                // zapis bez flush; finalny flush przy zapisie ToDo
                $this->tagRepo->save($tag, false);
            }
            $toDo->addTag($tag);
        }
    }

    /**
     * Rzuca wyjątek, jeśli użytkownik nie jest właścicielem ToDo.
     *
     * @param ToDo $toDo zadanie
     * @param User $user użytkownik do weryfikacji
     *
     * @return void
     *
     * @throws \LogicException
     */
    private function assertOwner(ToDo $toDo, User $user): void
    {
        if ($toDo->getUser()?->getId() !== $user->getId()) {
            throw new \LogicException('Operacja dostępna tylko dla właściciela.');
        }
    }

    /**
     * Rzuca wyjątek, jeśli użytkownik nie może edytować ToDo.
     *
     * @param ToDo $toDo zadanie
     * @param User $user użytkownik do weryfikacji
     *
     * @return void
     *
     * @throws \LogicException
     */
    private function assertCanEdit(ToDo $toDo, User $user): void
    {
        if (!$this->canEdit($toDo, $user)) {
            throw new \LogicException('Brak uprawnień do edycji.');
        }
    }
}
