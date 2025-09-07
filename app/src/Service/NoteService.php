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
use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use function array_filter;
use function array_map;
use function bin2hex;
use function explode;
use function is_dir;
use function mkdir;
use function random_bytes;
use function rtrim;
use function trim;
use function unlink;

/**
 * NoteService.
 */
final class NoteService implements NoteServiceInterface
{
    /**
     * @param NoteRepository     $noteRepo     Repozytorium notatek
     * @param CategoryRepository $categoryRepo Repozytorium kategorii
     * @param TagRepository      $tagRepo      Repozytorium tagów
     * @param string             $uploadsDir   Bezwzględna ścieżka do katalogu uploadów (może być pusty)
     */
    public function __construct(private readonly NoteRepository $noteRepo, private readonly CategoryRepository $categoryRepo, private readonly TagRepository $tagRepo, private readonly string $uploadsDir = '')
    {
    }

    /**
     * Buduje zapytanie listujące notatki dla użytkownika z filtrami.
     *
     * @param User                                                                           $user    Właściciel notatek
     * @param array{category?: int|string|null, tag?: int|string|null, search?: string|null} $filters Filtry listy
     *
     * @return QueryBuilder Query builder do dalszego przetwarzania (paginacja itp.)
     */
    public function buildListForUser(User $user, array $filters = []): QueryBuilder
    {
        return $this->noteRepo->queryListForUser($user, $filters);
    }

    /**
     * Zwraca notatkę o podanym ID, jeżeli należy do wskazanego właściciela.
     *
     * @param int  $id    Identyfikator notatki
     * @param User $owner Oczekiwany właściciel notatki
     *
     * @return Note|null Notatka lub null, gdy nie istnieje lub nie należy do właściciela
     */
    public function findOwned(int $id, User $owner): ?Note
    {
        $note = $this->noteRepo->find($id);
        if (!$note || $note->getUser()?->getId() !== $owner->getId()) {
            return null;
        }

        return $note;
    }

    /**
     * Zwraca listę kategorii użytkownika.
     *
     * @param User $owner Właściciel kategorii
     *
     * @return array<int, Category> Tablica kategorii użytkownika
     */
    public function listCategoriesForUser(User $owner): array
    {
        return $this->categoryRepo->findBy(['user' => $owner]);
    }

    /**
     * Tworzy nową notatkę wraz z kategorią, tagami i obrazem (jeśli podane).
     *
     * @param Note              $note         Tworzona notatka (z danymi formularza)
     * @param User              $owner        Właściciel notatki
     * @param string|null       $categoryName Nazwa kategorii do przypisania/utworzenia
     * @param string|null       $tagsCsv      Lista tagów w CSV
     * @param UploadedFile|null $imageFile    Plik obrazu
     *
     * @return Note Zapisana notatka
     */
    public function create(Note $note, User $owner, ?string $categoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note
    {
        $now = new DateTimeImmutable();
        $note->setUser($owner);
        $note->setCreatedAt($now);
        $note->setUpdatedAt($now);

        $this->applyCategoryByName($note, $owner, $categoryName);
        $this->applyTags($note, $tagsCsv);
        $this->applyImage($note, $imageFile);

        $this->noteRepo->save($note, true);

        return $note;
    }

    /**
     * Aktualizuje istniejącą notatkę (kategoria, tagi, obraz).
     *
     * @param Note              $note         Aktualizowana notatka
     * @param string|null       $categoryName Nazwa kategorii do przypisania/utworzenia
     * @param string|null       $tagsCsv      Lista tagów w CSV
     * @param UploadedFile|null $imageFile    Plik obrazu
     *
     * @return Note Zapisana notatka
     */
    public function update(Note $note, ?string $categoryName, ?string $tagsCsv, ?UploadedFile $imageFile): Note
    {
        $note->setUpdatedAt(new DateTimeImmutable());

        $this->applyCategoryByName($note, $note->getUser(), $categoryName);
        $this->applyTags($note, $tagsCsv);
        $this->applyImage($note, $imageFile);

        $this->noteRepo->save($note, true);

        return $note;
    }

    /**
     * Usuwa notatkę oraz powiązany plik obrazu (jeśli istnieje).
     *
     * @param Note $note Notatka do usunięcia
     */
    public function delete(Note $note): void
    {
        if (null !== $note->getImage() && '' !== $this->uploadsDir) {
            @unlink(rtrim($this->uploadsDir, '/').'/'.$note->getImage());
        }
        $this->noteRepo->remove($note, true);
    }

    /**
     * Zwraca tagi użytkownika (posortowane po nazwie).
     *
     * @param User $owner właściciel tagów
     *
     * @return array<int, Tag> lista tagów użytkownika
     */
    public function listTagsForUser(User $owner): array
    {
        return $this->tagRepo->findBy(['user' => $owner], ['name' => 'ASC']);
    }

    /**
     * Ustawia kategorię po nazwie: znajdź istniejącą właściciela lub utwórz nową.
     *
     * @param Note        $note         Notatka do modyfikacji
     * @param User        $owner        Właściciel notatki
     * @param string|null $categoryName Nazwa kategorii lub null
     *
     * @return void brak wyników
     */
    private function applyCategoryByName(Note $note, User $owner, ?string $categoryName): void
    {
        $name = trim((string) $categoryName);
        if ('' === $name) {
            return;
        }

        $existing = $this->categoryRepo->findOneBy(['user' => $owner, 'name' => $name]);
        if (null !== $existing) {
            $note->setCategory($existing);

            return;
        }

        $category = (new Category())
            ->setName($name)
            ->setUser($owner);

        $this->categoryRepo->save($category, false);
        $note->setCategory($category);
    }

    /**
     * Zastępuje tagi notatki listą z CSV; tagi są per-user.
     *
     * @param Note        $note    Notatka do modyfikacji
     * @param string|null $tagsCsv Lista tagów jako CSV (lub null, aby nic nie zmieniać)
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
        $owner = $note->getUser();

        foreach ($names as $name) {
            if ('' === $name) {
                continue;
            }
            $tag = $this->tagRepo->findOneBy(['name' => $name, 'user' => $owner]);
            if (null === $tag) {
                $tag = (new Tag())
                    ->setName($name)
                    ->setUser($owner);
                $this->tagRepo->save($tag, false);
            }
            $note->addTag($tag);
        }
    }

    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * Zapisuje plik obrazu i podmienia ewentualny poprzedni.
     *
     * @param Note              $note Notatka do modyfikacji
     * @param UploadedFile|null $file Przesłany plik obrazu lub null
     */
    private function applyImage(Note $note, ?UploadedFile $file): void
    {
        if (null === $file || '' === $this->uploadsDir) {
            return;
        }

        if (!$file->isValid()) {
            throw new RuntimeException();
        }

        if (null !== $note->getImage()) {
            @unlink(rtrim($this->uploadsDir, '/').'/'.$note->getImage());
        }

        $mime = (string) $file->getMimeType();
        $ext  = self::ALLOWED_MIME[$mime] ?? null;
        if (null === $ext) {
            throw new RuntimeException();
        }

        if (!is_dir($this->uploadsDir)) {
            @mkdir($this->uploadsDir, 0775, true);
        }

        $name = bin2hex(random_bytes(16)).'.'.$ext;

        try {
            $file->move($this->uploadsDir, $name);
        } catch (FileException $e) {
            throw new RuntimeException('Nie udało się zapisać pliku.', 0, $e);
        }

        $note->setImage($name);
    }
}
