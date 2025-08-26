<?php
/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Category;
use App\Entity\User;

/**
 * Interface for the Category service layer.
 *
 * Provides operations for listing, creating, updating and deleting categories
 * with ownership checks delegated to the implementation.
 */

interface CategoryServiceInterface
{
    /**
     * Returns the list of categories for the given user along with related counters.
     *
     * @param User $user The owner of the categories.
     *
     * @return array<int, array{0: Category, todoCount: int|string, noteCount: int|string}>
     */
    public function getListForUserWithCounts(User $user): array;

    /**
     * Creates a new category for the given user.
     *
     * @param Category $category The category to create.
     * @param User     $user     The owner of the category.
     *
     * @return Category The persisted category.
     */
    public function create(Category $category, User $user): Category;

    /**
     * Updates an existing category. Ownership should be enforced by the implementation.
     *
     * @param Category $category The category to update.
     * @param User     $user     The acting user (must be the owner).
     *
     * @return Category The updated category.
     */
    public function update(Category $category, User $user): Category;

    /**
     * Deletes a category. Ownership should be enforced by the implementation.
     *
     * @param Category $category The category to delete.
     * @param User     $user     The acting user (must be the owner).
     *
     * @return void
     */
    public function delete(Category $category, User $user): void;
}
