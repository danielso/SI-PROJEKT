<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\ToDo;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;

/**
 * Interface defining domain operations for managing ToDo items:
 * listing with filters, CRUD, permissions, categories/tags, and collaborators.
 */
interface ToDoServiceInterface
{
    /**
     * Builds a query for listing ToDo items visible to the given user with optional filters.
     *
     * @param User                                                                                                                $user    The user (context)
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null, scope?: 'mine'|'shared'|string|null} $filters Optional filters
     *
     * @return QueryBuilder Query builder for further processing (pagination etc.)
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder;

    /**
     * Finds a ToDo by its share token.
     *
     * @param string $token Share token
     *
     * @return ToDo|null The ToDo or null when not found
     */
    public function findOneByShareToken(string $token): ?ToDo;

    /**
     * Gets a ToDo if it belongs to the owner.
     *
     * @param int  $id    ToDo identifier
     * @param User $owner Expected owner
     *
     * @return ToDo|null The ToDo or null when not found / not owned by user
     */
    public function findOwned(int $id, User $owner): ?ToDo;

    /**
     * Gets a ToDo if the user can view it (owner or collaborator).
     *
     * @param int  $id   ToDo identifier
     * @param User $user Requesting user
     *
     * @return ToDo|null The ToDo or null when not accessible
     */
    public function findOwnedOrShared(int $id, User $user): ?ToDo;

    /**
     * Creates a new ToDo.
     *
     * @param ToDo        $toDo         New ToDo entity
     * @param User        $owner        Owner to assign
     * @param string|null $categoryName Free-text category name (optional)
     * @param string|null $tagsCsv      Comma-separated tags (optional)
     *
     * @return ToDo Persisted ToDo
     */
    public function create(ToDo $toDo, User $owner, ?string $categoryName, ?string $tagsCsv): ToDo;

    /**
     * Updates an existing ToDo.
     *
     * @param ToDo        $toDo         ToDo to update
     * @param User        $actingUser   Acting user (permissions are checked)
     * @param string|null $categoryName Free-text category name (optional)
     * @param string|null $tagsCsv      Comma-separated tags (optional)
     *
     * @return ToDo Persisted ToDo
     */
    public function update(ToDo $toDo, User $actingUser, ?string $categoryName, ?string $tagsCsv): ToDo;

    /**
     * Deletes a ToDo (owner enforced by implementation).
     *
     * @param ToDo $toDo       ToDo to delete
     * @param User $actingUser Acting user (must be owner)
     */
    public function delete(ToDo $toDo, User $actingUser): void;

    /**
     * Checks whether the given user can view the ToDo.
     *
     * @param ToDo      $toDo ToDo entity
     * @param User|null $user User (nullable)
     *
     * @return bool True when visible
     */
    public function canView(ToDo $toDo, ?User $user): bool;

    /**
     * Checks whether the given user can edit the ToDo.
     *
     * @param ToDo      $toDo ToDo entity
     * @param User|null $user User (nullable)
     *
     * @return bool True when editable
     */
    public function canEdit(ToDo $toDo, ?User $user): bool;

    /**
     * Checks whether the given user can delete the ToDo.
     *
     * @param ToDo      $toDo ToDo entity
     * @param User|null $user User (nullable)
     *
     * @return bool True when deletable
     */
    public function canDelete(ToDo $toDo, ?User $user): bool;

    /**
     * Returns categories for the given user (sorted by name).
     *
     * @param User $user Owner of categories
     *
     * @return array<int, \App\Entity\Category> List of categories
     */
    public function getCategoriesFor(User $user): array;

    /**
     * Returns all tags (sorted by name).
     *
     * @return array<int, \App\Entity\Tag> List of tags
     */
    public function getAllTags(): array;

    /**
     * Adds a collaborator by email (owner-only action).
     *
     * @param ToDo   $toDo       Target ToDo
     * @param string $email      Collaborator email
     * @param User   $actingUser Acting user (must be owner)
     */
    public function addCollaboratorByEmail(ToDo $toDo, string $email, User $actingUser): void;

    /**
     * Removes a collaborator by their user ID (owner-only action).
     *
     * @param ToDo $toDo       Target ToDo
     * @param int  $userId     Collaborator user ID
     * @param User $actingUser Acting user (must be owner)
     */
    public function removeCollaboratorById(ToDo $toDo, int $userId, User $actingUser): void;

    /**
     * Toggles the "done" flag on a ToDo.
     *
     * @param ToDo $toDo       Target ToDo
     * @param User $actingUser Acting user (permissions checked)
     *
     * @return ToDo Persisted ToDo
     */
    public function toggleDone(ToDo $toDo, User $actingUser): ToDo;
}
