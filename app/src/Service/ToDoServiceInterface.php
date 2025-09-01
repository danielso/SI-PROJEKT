<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\ToDo;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;

/**
 * Contract for ToDo-related business operations:
 * listing, CRUD, permissions, and helper lookups.
 */
interface ToDoServiceInterface
{
    /**
     * Builds a query for listing ToDo items visible to the given user with optional filters.
     *
     * @param User                                                                                                                $user    the user for whom to list items
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null, scope?: 'mine'|'shared'|string|null} $filters
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder;

    /**
     * Finds a ToDo by its share token.
     *
     * @param string $token the share token
     */
    public function findOneByShareToken(string $token): ?ToDo;

    /**
     * Pobiera ToDo, jeśli należy do właściciela.
     *
     * @param int  $id    ID ToDo
     * @param User $owner użytkownik – oczekiwany właściciel
     *
     * @return ToDo|null toDo lub null, gdy nie istnieje albo nie należy do użytkownika
     */
    public function findOwned(int $id, User $owner): ?ToDo;

    /**
     * Pobiera ToDo, jeśli użytkownik ma do niego dostęp (właściciel lub współpracownik).
     *
     * @param int  $id   ID ToDo
     * @param User $user użytkownik
     *
     * @return ToDo|null toDo lub null, gdy brak dostępu albo nie istnieje
     */
    public function findOwnedOrShared(int $id, User $user): ?ToDo;

    /**
     * Creates a new ToDo for the given owner and persists it.
     *
     * @param ToDo        $toDo            the ToDo entity to populate
     * @param User        $owner           the owner of the ToDo
     * @param int|null    $categoryId      existing category ID to assign (optional)
     * @param string|null $newCategoryName name of a new category to create and assign (optional)
     * @param string|null $tagsCsv         comma-separated tag names (optional)
     *
     * @return ToDo the persisted ToDo
     */
    public function create(ToDo $toDo, User $owner, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv): ToDo;

    /**
     * Updates an existing ToDo and persists changes.
     *
     * @param ToDo        $toDo            the ToDo to update
     * @param User        $actingUser      the user performing the update (must be allowed to edit)
     * @param int|null    $categoryId      existing category ID to assign (optional)
     * @param string|null $newCategoryName name of a new category to create and assign (optional)
     * @param string|null $tagsCsv         comma-separated tag names (optional)
     *
     * @return ToDo the updated ToDo
     */
    public function update(ToDo $toDo, User $actingUser, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv): ToDo;

    /**
     * Deletes a ToDo (ownership should be enforced by the implementation).
     *
     * @param ToDo $toDo       the ToDo to delete
     * @param User $actingUser the acting user (should be the owner)
     */
    public function delete(ToDo $toDo, User $actingUser): void;

    /**
     * Checks whether the user can view the ToDo (owner or collaborator).
     *
     * @param ToDo      $toDo the ToDo to check
     * @param User|null $user the user (nullable)
     */
    public function canView(ToDo $toDo, ?User $user): bool;

    /**
     * Checks whether the user can edit the ToDo.
     *
     * @param ToDo      $toDo the ToDo to check
     * @param User|null $user the user (nullable)
     */
    public function canEdit(ToDo $toDo, ?User $user): bool;

    /**
     * Checks whether the user can delete the ToDo (owner only).
     *
     * @param ToDo      $toDo the ToDo to check
     * @param User|null $user the user (nullable)
     */
    public function canDelete(ToDo $toDo, ?User $user): bool;

    /**
     * Returns categories for the given user ordered by name.
     *
     * @param User $user the owner of the categories
     *
     * @return array<\App\Entity\Category>
     */
    public function getCategoriesFor(User $user): array;

    /**
     * Returns all tags ordered by name.
     *
     * @return array<\App\Entity\Tag>
     */
    public function getAllTags(): array;

    /**
     * Adds a collaborator to the ToDo using their email (owner-only action).
     *
     * @param ToDo   $toDo       the target ToDo
     * @param string $email      email of the user to add
     * @param User   $actingUser the acting user (must be the owner)
     */
    public function addCollaboratorByEmail(ToDo $toDo, string $email, User $actingUser): void;

    /**
     * Removes a collaborator from the ToDo by their user ID (owner-only action).
     *
     * @param ToDo $toDo       the target ToDo
     * @param int  $userId     ID of the user to remove
     * @param User $actingUser the acting user (must be the owner)
     */
    public function removeCollaboratorById(ToDo $toDo, int $userId, User $actingUser): void;

    /**
     * Toggles the "done" flag of a ToDo (edit permission required).
     *
     * @param ToDo $toDo       the ToDo to toggle
     * @param User $actingUser the acting user (must be allowed to edit)
     *
     * @return ToDo the updated ToDo
     */
    public function toggleDone(ToDo $toDo, User $actingUser): ToDo;
}
