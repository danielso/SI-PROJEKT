<?php
/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\Note;
use App\Form\NoteType;
use App\Repository\NoteRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\NoteServiceInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Controller for managing notes (CRUD operations).
 */
class NoteController extends AbstractController
{
    /**
     * NoteController constructor.
     *
     * @param NoteServiceInterface $notes Service handling note operations.
     */
    public function __construct(private readonly NoteServiceInterface $notes)
    {
    }

    /**
     * Displays the form to create a new note and handles its submission.
     *
     * @param Request            $request
     * @param CategoryRepository $categoryRepository
     *
     * @return Response
     */
    #[Route('/note/new', name: 'note_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $note = new Note();
        $form = $this->createForm(NoteType::class, $note, ['user' => $user]);
        $form->handleRequest($request);

        /** @var \App\Entity\Category|null $selectedCategory */
        $selectedCategory = $form->get('category')->getData();
        $categoryId       = $selectedCategory?->getId();

        $newCategoryName = $form->get('newCategory')->getData() ?: null;
        $tagsCsv         = $form->get('tags')->getData() ?: null;

        /** @var UploadedFile|null $uploaded */
        $uploaded = $form->get('image')->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->notes->create($note, $user, $categoryId, $newCategoryName, $tagsCsv, $uploaded);

            return $this->redirectToRoute('note_index');
        }

        return $this->render('note/new.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edits an existing note.
     *
     * @param Request        $request
     * @param NoteRepository $noteRepository
     * @param int            $id
     *
     * @return Response
     */
    #[Route('/note/{id}/edit', name: 'note_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NoteRepository $noteRepository, int $id): Response
    {
        $note = $noteRepository->find($id);
        if (!$note) {
            throw $this->createNotFoundException('Nie znaleziono notatki.');
        }
        if ($note->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(NoteType::class, $note);
        $form->handleRequest($request);

        /** @var \App\Entity\Category|null $selectedCategory */
        $selectedCategory = $form->get('category')->getData();
        $categoryId       = $selectedCategory?->getId();

        $newCategoryName = $form->get('newCategory')->getData() ?: null;
        $tagsCsv         = $form->get('tags')->getData() ?: null;

        /** @var UploadedFile|null $uploaded */
        $uploaded = $form->get('image')->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->notes->update($note, $categoryId, $newCategoryName, $tagsCsv, $uploaded);

            return $this->redirectToRoute('note_index');
        }

        return $this->render('note/edit.html.twig', ['form' => $form->createView(), 'note' => $note]);
    }


    /**
     * Deletes a note.
     *
     * @param Request        $request
     * @param NoteRepository $noteRepository
     * @param int            $id
     *
     * @return Response
     */
    #[Route('/note/{id}/delete', name: 'note_delete', methods: ['POST'])]
    public function delete(Request $request, NoteRepository $noteRepository, int $id): Response
    {
        $note = $noteRepository->find($id);
        if (!$note || $note->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$note->getId(), $request->request->get('_token'))) {
            $this->notes->delete($note);
        }

        return $this->redirectToRoute('note_index');
    }

    /**
     * Displays a list of notes and handles filtering.
     *
     * @param Request            $request
     * @param CategoryRepository $categoryRepository
     *
     * @return Response
     */
    #[Route('/notes', name: 'note_index')]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $filters = [
            'category' => $request->query->get('category'),
            'tag'      => $request->query->get('tag'),
            'search'   => $request->query->get('search'),
        ];

        $qb    = $this->notes->buildListForUser($user, $filters);
        $notes = $qb->getQuery()->getResult();

        $categories = $categoryRepository->findBy(['user' => $user]);

        return $this->render('note/index.html.twig', [
            'notes'            => $notes,
            'categories'       => $categories,
            'tags'             => [],
            'selectedCategory' => $filters['category'],
            'selectedTag'      => $filters['tag'],
            'searchTerm'       => $filters['search'],
        ]);
    }
}
