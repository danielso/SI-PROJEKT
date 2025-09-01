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
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * NoteService.
 */
final class NoteService implements NoteServiceInterface
{
    /**
     * Konstruktor serwisu notatek.
     *
     * @param NoteRepository     $noteRepo     repozytorium notatek.
     * @param CategoryRepository $categoryRepo repozytorium kategorii.
     * @param TagRepository      $tagRepo      repozytorium tagów.
     * @param string             $uploadsDir   bezwzględna ścieżka do katalogu uploadów (może być pusty).
     */
    public function __construct(private readonly NoteRepository $noteRepo, private readonly CategoryRepository $categoryRepo, private readonly TagRepository $tagRepo, private readonly string $uploadsDir = '')
    {
    }

    /**
     * Buduje zapytanie listujące notatki użytkownika.
     *
     * @param User                                                                           $user    właściciel.
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null} $filters filtry.
     *
     * @return QueryBuilder
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder
    {
        return $this->noteRepo->queryListForUser($user, $filters);
    }

    /**
     * Znajduje notatkę po id, ale tylko jeśli należy do podanego użytkownika.
     *
     * @param int  $id    id notatki.
     * @param User $owner właściciel.
     *
     * @return Note|null
     */
    public function findOwned(int $id, User $owner): ?Note
    {
        $note = $this->noteRepo->find($id);
        if (!$note || $note->getUser() !== $owner) {
            return null;
        }

        return $note;
    }

    /**
     * Zwraca listę kategorii należących do użytkownika.
     *
     * @param User $owner właściciel.
     *
     * @return array<Category>
     */
    public function listCategoriesForUser(User $owner): array
    {
        return $this->categoryRepo->findBy(['user' => $owner]);
    }

    /**
     * Tworzy nową notatkę wraz z kategorią, tagami i obrazem (jeśli podane).
     *
     * @param Note              $note            nowa notatka (z wypełnionym formularzem).
     * @param User              $owner           właściciel
     * @param int|null          $categoryId      istniejąca kategoria (opcjonalnie).
     * @param string|null       $newCategoryName nazwa nowej kategorii (opcjonalnie).
     * @param string|null       $tagsCsv         CSV z nazwami tagów (opcjonalnie).
     * @param UploadedFile|null $imageFile       przesłany plik obrazu (opcjonalnie).
     *
     * @return Note
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

        $this->noteRepo->save($note, true);

        return $note;
    }

    /**
     * Aktualizuje istniejącą notatkę (kategoria, tagi, obraz).
     *
     * @param Note              $note            notatka do aktualizacji.
     * @param int|null          $categoryId      istniejąca kategoria (opcjonalnie).
     * @param string|null       $newCategoryName nazwa nowej kategorii (opcjonalnie).
     * @param string|null       $tagsCsv         CSV z nazwami tagów (opcjonalnie).
     * @param UploadedFile|null $imageFile       przesłany plik obrazu (opcjonalnie).
     *
     * @return Note
     */
    public function update(Note $note, ?int $categoryId, ?string $newCategoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note
    {
        $note->setUpdatedAt(new \DateTimeImmutable());

        $this->applyCategory($note, $note->getUser(), $categoryId, $newCategoryName);
        $this->applyTags($note, $tagsCsv);
        $this->applyImage($note, $imageFile);

        $this->noteRepo->save($note, true);

        return $note;
    }

    /**
     * Usuwa notatkę oraz powiązany plik obrazu (jeśli istnieje).
     *
     * @param Note $note notatka do usunięcia.
     *
     * @return void
     */
    public function delete(Note $note): void
    {
        if (null !== $note->getImage() && '' !== $this->uploadsDir) {
            @unlink($this->uploadsDir.'/'.$note->getImage());
        }
        $this->noteRepo->remove($note, true);
    }

    /**
     * Ustawia kategorię notatki: istniejącą po id albo tworzy nową po nazwie.
     *
     * @param Note        $note            notatka.
     * @param User        $owner           właściciel notatki.
     * @param int|null    $categoryId      id istniejącej kategorii.
     * @param string|null $newCategoryName nazwa nowej kategorii.
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
            $this->categoryRepo->save($cat, false);
            $note->setCategory($cat);
        }
    }

    /**
     * Ustawia (nadpisuje) tagi notatki na podstawie CSV.
     *
     * @param Note        $note    notatka.
     * @param string|null $tagsCsv CSV z nazwami tagów.
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
                $this->tagRepo->save($tag, false);
            }
            $note->addTag($tag);
        }
    }

    /**
     * Zapisuje plik obrazu i podmienia ewentualny poprzedni.
     *
     * @param Note              $note notatka.
     * @param UploadedFile|null $file przesłany plik.
     *                                obrazu.
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
