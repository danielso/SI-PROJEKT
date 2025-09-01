<?php

/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Category;
use App\Entity\Note;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Contract for note-related business operations.
 */
interface NoteServiceInterface
{
    /**
     * Builds a query for listing notes of a given user with optional filters.
     *
     * @param User                                                                           $user    the owner of the notes.
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null} $filters filters.
     *
     * @return QueryBuilder
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder;

    /**
     * Finds a note by its id but only if it belongs to the given user.
     *
     * @param int  $id    note id.
     * @param User $owner expected owner of the note.
     *
     * @return Note|null
     */
    public function findOwned(int $id, User $owner): ?Note;

    /**
     * Lists all categories owned by the given user.
     *
     * @param User $owner owner of categories.
     *
     * @return Category[]
     */
    public function listCategoriesForUser(User $owner): array;

    /**
     * Creates and persists a new note.
     *
     * @param Note              $note            note entity to create.
     * @param User              $owner           note owner.
     * @param int|null          $categoryId      existing category id.
     * @param string|null       $newCategoryName new category name.
     * @param string|null       $tagsCsv         comma-separated tag names.
     * @param UploadedFile|null $imageFile       uploaded image file.
     *
     * @return Note
     */
    public function create(Note $note, User $owner, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note;

    /**
     * Updates an existing note.
     *
     * @param Note              $note            note entity to update.
     * @param int|null          $categoryId      existing category id.
     * @param string|null       $newCategoryName new category name.
     * @param string|null       $tagsCsv         comma-separated tag names.
     * @param UploadedFile|null $imageFile       uploaded image file.
     *
     * @return Note
     */
    public function update(Note $note, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note;

    /**
     * Deletes a note.
     *
     * @param Note $note note to delete
     *
     * @return void
     */
    public function delete(Note $note): void;
}
