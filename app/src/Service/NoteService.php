<?php
/**
 * @license MIT
 */

namespace App\Service;

use App\Entity\Category;
use App\Entity\Note;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\NoteRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service layer for Note domain operations (listing, create/update/delete, category/tags/image helpers).
 */
final class NoteService implements NoteServiceInterface
{
    /**
     * NoteService constructor.
     *
     * @param NoteRepository         $noteRepo     Repository for Note entities.
     * @param CategoryRepository     $categoryRepo Repository for Category entities.
     * @param TagRepository          $tagRepo      Repository for Tag entities.
     * @param EntityManagerInterface $em           Entity manager for persistence operations.
     * @param string                 $uploadsDir   Absolute path to the uploads directory (can be empty to disable file ops).
     */
    public function __construct(private readonly NoteRepository $noteRepo, private readonly CategoryRepository $categoryRepo, private readonly TagRepository $tagRepo, private readonly EntityManagerInterface $em, private readonly string $uploadsDir = '')
    {
    }

    /**
     * Builds a query for listing notes of a given user with optional filters.
     *
     * @param User  $user    Owner of the notes.
     * @param array $filters Optional filters: category, tag, search.
     *
     * @return QueryBuilder
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder
    {
        return $this->noteRepo->queryListForUser($user, $filters);
    }

    /**
     * Creates and persists a new note for the given owner.
     *
     * @param Note              $note            The note entity to populate.
     * @param User              $owner           The note owner.
     * @param int|null          $categoryId      Existing category ID to assign (optional).
     * @param string|null       $newCategoryName Name of a new category to create and assign (optional).
     * @param string|null       $tagsCsv         Comma-separated tag names (optional).
     * @param UploadedFile|null $imageFile       Uploaded image file (optional).
     *
     * @return Note The persisted note.
     */
    public function create(Note $note, User $owner, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note
    {
        $now = new \DateTimeImmutable();
        $note->setUser($owner);
        $note->setCreatedAt($now);
        $note->setUpdatedAt($now);

        $this->applyCategory($note, $owner, $categoryId, $newCategoryName);
        $this->applyTags($note, $tagsCsv);
        $this->applyImage($note, $imageFile);

        $this->em->persist($note);
        $this->em->flush();

        return $note;
    }

    /**
     * Updates an existing note (category/tags/image) and persists changes.
     *
     * @param Note              $note            The note to update.
     * @param int|null          $categoryId      Existing category ID to assign (optional).
     * @param string|null       $newCategoryName Name of a new category to create and assign (optional).
     * @param string|null       $tagsCsv         Comma-separated tag names (optional).
     * @param UploadedFile|null $imageFile       Uploaded image file (optional).
     *
     * @return Note The updated note.
     */
    public function update(Note $note, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note
    {
        $note->setUpdatedAt(new \DateTimeImmutable());

        $this->applyCategory($note, $note->getUser(), $categoryId, $newCategoryName);
        $this->applyTags($note, $tagsCsv);
        $this->applyImage($note, $imageFile);

        $this->em->flush();

        return $note;
    }

    /**
     * Deletes a note (and removes its image file if configured and present).
     *
     * @param Note $note The note to delete.
     *
     * @return void
     */
    public function delete(Note $note): void
    {
        if (null !== $note->getImage() && '' !== $this->uploadsDir) {
            @unlink($this->uploadsDir.'/'.$note->getImage());
        }
        $this->em->remove($note);
        $this->em->flush();
    }

    /**
     * Assigns a category to the note: either an existing one by ID or a newly created one by name.
     *
     * @param Note        $note            The note being modified.
     * @param User        $owner           The owner of the note (used when creating a new category).
     * @param int|null    $categoryId      Existing category ID to assign.
     * @param string|null $newCategoryName Name of a new category to create.
     *
     * @return void
     */
    private function applyCategory(Note $note, User $owner, ?int $categoryId, ?string $newCategoryName): void
    {
        $newCategoryName = $newCategoryName ? trim($newCategoryName) : null;

        if ($categoryId && ($cat = $this->categoryRepo->find((int) $categoryId))) {
            $note->setCategory($cat);

            return;
        }

        if ($newCategoryName) {
            $cat = (new Category())->setName($newCategoryName)->setUser($owner);
            $this->em->persist($cat);
            $note->setCategory($cat);
        }
    }

    /**
     * Replaces note tags with the set provided in CSV (creating missing tags if needed).
     *
     * @param Note        $note    The note being modified.
     * @param string|null $tagsCsv Comma-separated tag names; when null, tags remain unchanged.
     *
     * @return void
     */
    private function applyTags(Note $note, ?string $tagsCsv): void
    {
        if (null === $tagsCsv) {
            return;
        }

        foreach (clone $note->getTags() as $existing) {
            $note->removeTag($existing);
        }

        $names = array_filter(array_map('trim', explode(',', (string) $tagsCsv)));
        foreach ($names as $name) {
            $tag = $this->tagRepo->findOneBy(['name' => $name]);
            if (!$tag) {
                $tag = (new Tag())->setName($name);
                $this->em->persist($tag);
            }
            $note->addTag($tag);
        }
    }

    /**
     * Stores the uploaded image file and assigns its filename to the note (removing the old one if any).
     *
     * @param Note              $note The note being modified.
     * @param UploadedFile|null $file The uploaded file; when null or when uploadsDir is empty, nothing happens.
     *
     * @return void
     */
    private function applyImage(Note $note, ?UploadedFile $file): void
    {
        if (null === $file || '' === $this->uploadsDir) {
            return;
        }

        if ($note->getImage()) {
            @unlink($this->uploadsDir.'/'.$note->getImage());
        }

        $ext  = $file->guessExtension() ?: 'bin';
        $name = md5(uniqid('', true)).'.'.$ext;

        $file->move($this->uploadsDir, $name);

        $note->setImage($name);
    }
}
