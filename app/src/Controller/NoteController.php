<?php

/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\Note;
use App\Entity\User;
use App\Form\NoteType;
use App\Service\NoteServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\Voter\NoteVoter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for managing notes (CRUD operations).
 */
class NoteController extends AbstractController
{
    /**
     * NoteController constructor.
     *
     * @param NoteServiceInterface $notes Service handling note operations
     */
    public function __construct(private readonly NoteServiceInterface $notes)
    {
    }

    /**
     * Displays the form to create a new note and handles its submission.
     *
     * @param Request $request HTTP request with form data
     *
     * @return Response Rendered create form
     */
    #[Route('/note/new', name: 'note_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            return $this->redirectToRoute('app_login');
        }

        $note = new Note();
        $note->setUser($user);
        if (null === $note->getCreatedAt()) {
            $now = new \DateTimeImmutable();
            $note->setCreatedAt($now);
            $note->setUpdatedAt($now);
        }

        $form = $this->createForm(NoteType::class, $note, ['user' => $user]);
        $form->handleRequest($request);

        $categoryName = $form->get('categoryName')->getData() ?: null;
        $tagsCsv      = $form->get('tags')->getData() ?: null;
        /** @var UploadedFile|null $uploaded */
        $uploaded     = $form->get('image')->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->notes->create($note, $user, $categoryName, $tagsCsv, $uploaded);

            return $this->redirectToRoute('note_index');
        }

        return $this->render('note/new.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edits an existing note.
     *
     * @param Request $request HTTP request with form data
     * @param int     $id      Note ID
     *
     * @return Response Rendered edit form or redirect on success
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException When the note is not found
     */
    #[Route('/note/{id}/edit', name: 'note_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            return $this->redirectToRoute('app_login');
        }

        $note = $this->notes->findOwned($id, $user);
        if (!$note) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(NoteType::class, $note, ['user' => $user]);
        $form->handleRequest($request);

        $categoryName = $form->get('categoryName')->getData() ?: null;
        $tagsCsv      = $form->get('tags')->getData() ?: null;
        /** @var UploadedFile|null $uploaded */
        $uploaded     = $form->get('image')->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->notes->update($note, $categoryName, $tagsCsv, $uploaded);

            return $this->redirectToRoute('note_index');
        }

        return $this->render('note/edit.html.twig', [
            'form' => $form->createView(),
            'note' => $note,
        ]);
    }

    /**
     * Deletes a note.
     *
     * @param Request             $request    HTTP request with CSRF token
     * @param int                 $id         Note ID
     * @param TranslatorInterface $translator Translator for flash messages
     *
     * @return Response Redirect to index after deletion
     */
    #[Route('/note/{id}/delete', name: 'note_delete', methods: ['GET', 'DELETE'])]
    public function delete(Request $request, int $id, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            return $this->redirectToRoute('app_login');
        }

        $note = $this->notes->findOwned($id, $user);
        if (!$note) {
            $this->addFlash('warning', $translator->trans('message.record_not_found'));

            return $this->redirectToRoute('note_index');
        }

        $this->denyAccessUnlessGranted(NoteVoter::DELETE, $note);

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('note_delete', ['id' => $note->getId()]))
            ->setMethod('DELETE')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->notes->delete($note);
            $this->addFlash('success', $translator->trans('message.deleted_successfully'));

            return $this->redirectToRoute('note_index');
        }

        return $this->render('note/delete.html.twig', [
            'form' => $form->createView(),
            'note' => $note,
        ]);
    }

    /**
     * Displays a list of notes and handles filtering.
     *
     * @param Request $request HTTP request with filter query parameters
     *
     * @return Response Rendered list page
     */
    #[Route('/notes', name: 'note_index')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            return $this->redirectToRoute('app_login');
        }

        $filters = [
            'category' => $request->query->get('category'),
            'tag'      => $request->query->get('tag'),
            'search'   => $request->query->get('search'),
        ];

        $qb    = $this->notes->buildListForUser($user, $filters);
        $notes = $qb->getQuery()->getResult();

        $categories = $this->notes->listCategoriesForUser($user);
        $tags       = $this->notes->listTagsForUser($user);

        return $this->render('note/index.html.twig', [
            'notes'            => $notes,
            'categories'       => $categories,
            'tags'             => $tags,
            'selectedCategory' => $filters['category'],
            'selectedTag'      => $filters['tag'],
            'searchTerm'       => $filters['search'],
        ]);
    }
}
