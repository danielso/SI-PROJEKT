<?php

// src/Controller/NoteController.php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\Category;
use App\Entity\Tag;
use App\Form\NoteType;
use App\Repository\NoteRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\EntityType;

class NoteController extends AbstractController
{
    private $noteRepository;

    // Wstrzykujemy NoteRepository do konstruktor
    public function __construct(NoteRepository $noteRepository)
    {
        $this->noteRepository = $noteRepository;  // Przypisanie repozytorium do zmiennej
    }

    #[Route('/note/new', name: 'note_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository): Response
    {
        $note = new Note();

        $note->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(NoteType::class, $note, [
            'user' => $this->getUser(), // Przekazujemy użytkownika do formularza
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Przypisanie użytkownika do notatki
            $user = $this->getUser();
            if ($user) {
                $note->setUser($user);
            } else {
                return $this->redirectToRoute('app_login');
            }

            $categoryName = $form->get('category')->getData();
            $newCategoryName = $form->get('newCategory')->getData();

            if ($categoryName) {
                $category = $categoryRepository->find($categoryName);
                $note->setCategory($category);
            } elseif ($newCategoryName) {
                $category = new Category();
                $category->setName($newCategoryName);
                $category->setUser($this->getUser());
                $em->persist($category);
                $em->flush();
                $note->setCategory($category);
            }

            // Obsługuje tagi
            $tagsString = $form->get('tags')->getData();
            if ($tagsString) {
                $tagNames = array_map('trim', explode(',', $tagsString));
                foreach ($tagNames as $tagName) {
                    if (!$tagName) continue;

                    $tag = $em->getRepository(Tag::class)->findOneBy(['name' => $tagName]);
                    if (!$tag) {
                        $tag = new Tag();
                        $tag->setName($tagName);
                        $em->persist($tag);
                    }
                    $note->addTag($tag);
                }
            }

            // Obsługuje plik graficzny
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                dump($imageFile); // Sprawdzamy, czy plik rzeczywiście jest dostępny
                $imageName = md5(uniqid()) . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('uploads_directory'),  // Katalog przechowania pliku
                    $imageName
                );
                $note->setImage($imageName);
            } else {
                dump('No image file uploaded');
            }


            $em->persist($note);
            $em->flush();

            return $this->redirectToRoute('note_index');
        }

        return $this->render('note/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/note/{id}/edit', name: 'note_edit')]
    public function edit(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepository, NoteRepository $noteRepository, int $id): Response
    {
        $note = $noteRepository->find($id);

        if (!$note) {
            throw $this->createNotFoundException('Nie znaleziono notatki.');
        }

        // Sprawdzenie, czy użytkownik jest właścicielem notatki
        $user = $this->getUser();
        if ($note->getUser() !== $user) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do edycji tej notatki.');
        }

        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            dump($note);

            // Obsługuje plik graficzny
            $imageFile = $form->get('image')->getData();  // Pobieramy przesłany plik
            if ($imageFile) {
                // Sprawdzenie, czy istnieje stary obrazek i jego usunięcie
                $oldImage = $note->getImage();
                if ($oldImage) {
                    // Usuń stary plik (upewnij się, że masz poprawną ścieżkę do pliku)
                    unlink($this->getParameter('uploads_directory') . '/' . $oldImage);
                }

                // Zapisanie nowego pliku
                $imageName = md5(uniqid()) . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('uploads_directory'),
                    $imageName
                );
                $note->setImage($imageName);
            }

            // Przypisanie kategorii
            $categoryId = $form->get('category')->getData();
            if ($categoryId) {
                $category = $categoryRepository->find($categoryId);
                if ($category) {
                    $note->setCategory($category);
                }
            } else {
                $note->setCategory(null);
            }

            $note->setUpdatedAt(new \DateTimeImmutable());

            $em->flush();

            // Po zapisaniu przekierowujemy na widok listy notatek
            return $this->redirectToRoute('note_index');
        } else {
            dump($form->getErrors(true));
        }

        // Jeśli formularz nie jest wysłany lub nie jest ważny, renderuje widok z formularzem
        return $this->render('note/edit.html.twig', [
            'form' => $form->createView(),
            'note' => $note,
        ]);
    }


    // Usuwanie notatki
    #[Route('/note/{id}/delete', name: 'note_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $em, int $id): Response
    {

        $note = $this->noteRepository->find($id);

        //czy użytkownik jest właścicielem notatki
        $user = $this->getUser();
        if (!$note || $note->getUser() !== $user) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do edycji tej notatki.');
        }

        // Usunięcie notatki
        $em->remove($note);
        $em->flush();

        return $this->redirectToRoute('note_index');
    }

    // Wyświetlanie listy notatek
    #[Route('/notes', name: 'note_index')]
    public function index(Request $request, NoteRepository $noteRepository, CategoryRepository $categoryRepository, \App\Repository\TagRepository $tagRepository): Response
    {
        $user = $this->getUser();  // Pobieramy zalogowanego użytkownika
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        //QueryBuilder
        $qb = $noteRepository->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC');

        // Pobiera przekazane parametry do filtrowania
        $categoryId = $request->query->get('category');
        $tagId = $request->query->get('tag');
        $searchTerm = $request->query->get('search');

        // Filtracja po kategorii
        if ($categoryId) {
            $qb->andWhere('n.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        // Filtracja po tagu
        if ($tagId) {
            $qb->leftJoin('n.tags', 't')
                ->andWhere('t.id = :tagId')
                ->setParameter('tagId', $tagId);
        }

        // Filtracja po wyszukiwanie w tytule i treści
        if ($searchTerm) {
            $qb->andWhere('LOWER(n.title) LIKE :searchTerm OR LOWER(n.content) LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');
        }

        $notes = $qb->getQuery()->getResult();

        $categories = $categoryRepository->findBy(['user' => $user]);

        return $this->render('note/index.html.twig', [
            'notes' => $notes,
            'categories' => $categories,
            'tags' => $tagRepository->findAll(),
            'selectedCategory' => $categoryId,
            'selectedTag' => $tagId,
            'searchTerm' => $searchTerm,
        ]);
    }
}
