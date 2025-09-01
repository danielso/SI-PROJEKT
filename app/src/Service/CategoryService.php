<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Warstwa serwisowa dla kategorii (CRUD, listy z licznikami).
 */
final class CategoryService implements CategoryServiceInterface
{
    /**
     * @param CategoryRepository $categoryRepository repozytorium kategorii.
     */
    public function __construct(private readonly CategoryRepository $categoryRepository)
    {
    }

    /**
     * Returns categories for the given user with item counters.
     *
     * @param User $user category owner
     *
     * @return array<int, array{0: Category, todoCount: int|string, noteCount: int|string}>
     */
    public function getListForUserWithCounts(User $user): array
    {
        return $this->categoryRepository->findAllForUserWithCounts($user);
    }

    /**
     * Creates a new category for the given user.
     *
     * @param Category $category category to create
     * @param User     $user     owner to assign
     *
     * @return Category
     */
    public function create(Category $category, User $user): Category
    {
        $category->setUser($user);
        $this->categoryRepository->save($category, true);

        return $category;
    }

    /**
     * Updates a category after ownership check.
     *
     * @param Category $category category to update
     * @param User     $user     acting user
     *
     * @return Category
     *
     * @throws AccessDeniedException when the user is not the owner
     */
    public function update(Category $category, User $user): Category
    {
        $this->assertOwner($category, $user);
        $this->categoryRepository->save($category, true);

        return $category;
    }

    /**
     * Deletes a category after ownership check.
     *
     * @param Category $category category to delete
     * @param User     $user     acting user
     *
     * @throws AccessDeniedException when the user is not the owner
     */
    public function delete(Category $category, User $user): void
    {
        $this->assertOwner($category, $user);
        $this->categoryRepository->remove($category, true);
    }


    /**
     * Weryfikuje, czy użytkownik jest właścicielem kategorii.
     *
     * @param Category $category kategoria do sprawdzenia.
     * @param User     $user     sprawdzany użytkownik.
     *
     * @throws AccessDeniedException gdy użytkownik nie jest właścicielem.
     */
    private function assertOwner(Category $category, User $user): void
    {
        if ($category->getUser() !== $user) {
            throw new AccessDeniedException();
        }
    }
}
