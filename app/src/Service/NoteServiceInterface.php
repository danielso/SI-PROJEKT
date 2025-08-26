<?php
/**
 * @license MIT
 */

namespace App\Service;

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
     * @param User                                                                           $user    The owner of the notes.
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null} $filters
     *
     * @return QueryBuilder
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder;

    /**
     * Creates and persists a new note.
     *
     * @param Note              $note
     * @param User              $owner
     * @param int|null          $categoryId
     * @param string|null       $newCategoryName
     * @param string|null       $tagsCsv
     * @param UploadedFile|null $imageFile
     *
     * @return Note
     */
    public function create(Note $note, User $owner, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note;

    /**
     * Updates an existing note.
     *
     * @param Note              $note
     * @param int|null          $categoryId
     * @param string|null       $newCategoryName
     * @param string|null       $tagsCsv
     * @param UploadedFile|null $imageFile
     *
     * @return Note
     */
    public function update(Note $note, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note;

    /**
     * Deletes a note.
     *
     * @param Note $note
     *
     * @return void
     */
    public function delete(Note $note): void;
}
