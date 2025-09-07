<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\NoteRepository;
use App\Repository\ToDoRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Warstwa serwisowa dla kategorii (CRUD, listy z licznikami).
 */
final class CategoryService implements CategoryServiceInterface
{
    /**
     * CategoryService constructor.
     *
     * @param CategoryRepository $categoryRepository Repozytorium kategorii
     * @param NoteRepository     $noteRepository     Repozytorium notatek
     * @param ToDoRepository     $toDoRepository     Repozytorium zadań ToDo
     */
    public function __construct(private readonly CategoryRepository $categoryRepository, private readonly NoteRepository $noteRepository, private readonly ToDoRepository $toDoRepository)
    {
    }

    /**
     * Zwraca kategorie użytkownika wraz z licznikami.
     *
     * @param User $user Właściciel kategorii
     *
     * @return array<int, array{0: Category, todoCount: int|string, noteCount: int|string}> Lista kategorii z licznikami
     */
    public function getListForUserWithCounts(User $user): array
    {
        return $this->categoryRepository->findAllForUserWithCounts($user);
    }

    /**
     * Tworzy nową kategorię dla wskazanego użytkownika.
     *
     * @param Category $category Kategoria do utworzenia
     * @param User     $user     Właściciel do przypisania
     *
     * @return Category Utworzona kategoria
     */
    public function create(Category $category, User $user): Category
    {
        $category->setUser($user);
        $this->categoryRepository->save($category, true);

        return $category;
    }

    /**
     * Aktualizuje kategorię po weryfikacji własności.
     *
     * @param Category $category Kategoria do aktualizacji
     * @param User     $user     Użytkownik wykonujący operację
     *
     * @return Category Zaktualizowana kategoria
     *
     * @throws AccessDeniedException Gdy użytkownik nie jest właścicielem
     */
    public function update(Category $category, User $user): Category
    {
        $this->assertOwner($category, $user);
        $this->categoryRepository->save($category, true);

        return $category;
    }

    /**
     * Usuwa kategorię po weryfikacji własności.
     *
     * @param Category $category Kategoria do usunięcia
     * @param User     $user     Użytkownik wykonujący operację
     *
     * @return void Brak rezultatu
     *
     * @throws AccessDeniedException Gdy użytkownik nie jest właścicielem
     */
    public function delete(Category $category, User $user): void
    {
        $this->assertOwner($category, $user);
        $this->categoryRepository->remove($category, true);
    }

    /**
     * Określa, czy kategorię można usunąć.
     *
     * @param Category $category Kategoria do sprawdzenia
     *
     * @return bool True, jeśli nie ma powiązanych notatek ani zadań
     */
    public function canBeDeleted(Category $category): bool
    {
        $noteCount = $this->noteRepository->count(['category' => $category]);
        $todoCount = $this->toDoRepository->count(['category' => $category]);

        return 0 === $noteCount && 0 === $todoCount;
    }

    /**
     * Weryfikuje, czy użytkownik jest właścicielem kategorii.
     *
     * @param Category $category Kategoria do sprawdzenia
     * @param User     $user     Sprawdzany użytkownik
     *
     * @return void Brak rezultatu
     *
     * @throws AccessDeniedException Gdy użytkownik nie jest właścicielem
     */
    private function assertOwner(Category $category, User $user): void
    {
        if ($category->getUser() !== $user) {
            throw new AccessDeniedException();
        }
    }
}
