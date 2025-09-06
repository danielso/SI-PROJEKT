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
     * @param User $user the owner of the categories
     *
     * @return array<int, array{0: Category, todoCount: int|string, noteCount: int|string}>
     */
    public function getListForUserWithCounts(User $user): array;

    /**
     * Creates a new category for the given user.
     *
     * @param Category $category the category to create
     * @param User     $user     the owner of the category
     *
     * @return Category the persisted category
     */
    public function create(Category $category, User $user): Category;

    /**
     * Updates an existing category. Ownership should be enforced by the implementation.
     *
     * @param Category $category the category to update
     * @param User     $user     the acting user (must be the owner)
     *
     * @return Category the updated category
     */
    public function update(Category $category, User $user): Category;

    /**
     * Deletes a category. Ownership should be enforced by the implementation.
     *
     * @param Category $category the category to delete
     * @param User     $user     the acting user (must be the owner)
     */
    public function delete(Category $category, User $user): void;

    /**
     * Can Category be deleted?
     *
     * @param Category $category Category entity
     *
     * @return bool Result
     */
    public function canBeDeleted(Category $category): bool;
}
