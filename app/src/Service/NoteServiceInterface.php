<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Category;
use App\Entity\Note;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Interface defining domain operations for managing notes.
 */
interface NoteServiceInterface
{
    /**
     * Builds a query listing notes for a given user with optional filters.
     *
     * @param User                                                                           $user    Owner of the notes
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null} $filters Optional filters
     *
     * @return QueryBuilder Query builder for further processing (e.g., pagination)
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder;

    /**
     * Finds a note by ID only if it is owned by the provided user.
     *
     * @param int  $id    Note identifier
     * @param User $owner Expected owner
     *
     * @return Note|null The note if found and owned by the user, otherwise null
     */
    public function findOwned(int $id, User $owner): ?Note;

    /**
     * Lists categories belonging to the given user.
     *
     * @param User $owner Category owner
     *
     * @return array<int, Category> User's categories
     */
    public function listCategoriesForUser(User $owner): array;

    /**
     * Creates and persists a note. Category is resolved by name (find or create).
     *
     * @param Note              $note         Note entity carrying form data
     * @param User              $owner        Owner to assign
     * @param string|null       $categoryName Category name to assign/create
     * @param string|null       $tagsCsv      Comma-separated tag list
     * @param UploadedFile|null $imageFile    Uploaded image file (optional)
     *
     * @return Note Persisted note
     */
    public function create(Note $note, User $owner, ?string $categoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note;

    /**
     * Updates an existing note. Category is resolved by name (find or create).
     *
     * @param Note              $note         Note to update
     * @param string|null       $categoryName Category name to assign/create
     * @param string|null       $tagsCsv      Comma-separated tag list
     * @param UploadedFile|null $imageFile    Uploaded image file (optional)
     *
     * @return Note Persisted note
     */
    public function update(Note $note, ?string $categoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note;

    /**
     * Deletes a note (and its associated image file if present).
     *
     * @param Note $note Note to delete
     */
    public function delete(Note $note): void;

    /**
     * Lists tags belonging to the given user (sorted by name).
     *
     * @param User $owner Owner of tags
     *
     * @return array<int, Tag> User's tags
     */
    public function listTagsForUser(User $owner): array;
}
