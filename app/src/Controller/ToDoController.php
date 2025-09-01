<?php

/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\ToDo;
use App\Form\ToDoForm;
use App\Service\ToDoServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for managing ToDo items (listing, CRUD, sharing, collaborators).
 */
#[Route('/to/do')]
final class ToDoController extends AbstractController
{
    /**
     * ToDoController constructor.
     *
     * @param ToDoServiceInterface $toDo service handling ToDo operations.
     */
    public function __construct(private readonly ToDoServiceInterface $toDo)
    {
    }

    /**
     * Lists ToDo items for the current user with optional filters.
     *
     * @param Request $request HTTP request with query parameters.
     *
     * @return Response Rendered list page.
     */
    #[Route('', name: 'app_to_do_index', methods: ['GET'])]
    public function index(Request $request): Response
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

        $qb       = $this->toDo->buildListForUser($user, $filters);
        $toDoList = $qb->getQuery()->getResult();

        $categories = $this->toDo->getCategoriesFor($user);
        $tags       = $this->toDo->getAllTags();

        return $this->render('to_do/index.html.twig', [
            'to_do'            => $toDoList,
            'categories'       => $categories,
            'tags'             => $tags,
            'selectedCategory' => $filters['category'],
            'selectedTag'      => $filters['tag'],
            'searchTerm'       => $filters['search'],
        ]);
    }

    /**
     * Creates a new ToDo for the current user.
     *
     * @param Request $request HTTP request with form data.
     *
     * @return Response Rendered create form or redirect on success.
     */
    #[Route('/new', name: 'app_to_do_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $toDo = new ToDo();
        $toDo->setUser($user);
        $toDo->setIsDone(false);
        if (null === $toDo->getCreatedAt()) {
            $toDo->setCreatedAt(new \DateTimeImmutable());
        }

        $form = $this->createForm(ToDoForm::class, $toDo, ['user' => $user]);
        $form->handleRequest($request);

        /** @var \App\Entity\Category|null $selectedCategory */
        $selectedCategory = $form->get('category')->getData();
        $categoryId       = $selectedCategory?->getId();
        $newCategoryName  = $form->get('newCategory')->getData() ?: null;
        $tagsCsv          = $form->get('tags')->getData() ?: null;

        if ($form->isSubmitted() && $form->isValid()) {
            $this->toDo->create($toDo, $user, $categoryId, $newCategoryName, $tagsCsv);

            return $this->redirectToRoute('app_to_do_index');
        }
        $status = $form->isSubmitted() && !$form->isValid() ? 422 : 200;

        return $this->render('to_do/new.html.twig', [
            'form' => $form->createView(),
        ], new Response('', $status));
    }

    /**
     * Shows a single ToDo if the current user can view it.
     *
     * @param ToDo $toDo ToDo to display.
     *
     * @return Response Rendered details page.
     */
    #[Route('/{id}', name: 'app_to_do_show', methods: ['GET'], requirements: ['id' => '[1-9]\d*'])]
    public function show(ToDo $toDo): Response
    {
        $user = $this->getUser();
        if (!$this->toDo->canView($toDo, $user)) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('to_do/show.html.twig', [
            'to_do' => $toDo,
        ]);
    }

    /**
     * Edits an existing ToDo if the current user can edit it.
     *
     * @param Request $request HTTP request with form data.
     * @param ToDo    $toDo    ToDo being edited.
     *
     * @return Response Rendered edit form or redirect on success.
     */
    #[Route('/{id}/edit', name: 'app_to_do_edit', methods: ['GET', 'POST'], requirements: ['id' => '[1-9]\d*'])]
    public function edit(Request $request, ToDo $toDo): Response
    {
        $user = $this->getUser();
        if (!$this->toDo->canEdit($toDo, $user)) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ToDoForm::class, $toDo, ['user' => $user]);
        $form->handleRequest($request);

        /** @var \App\Entity\Category|null $selectedCategory */
        $selectedCategory = $form->get('category')->getData();
        $categoryId       = $selectedCategory?->getId();
        $newCategoryName  = $form->get('newCategory')->getData() ?: null;
        $tagsCsv          = $form->has('tags') ? $form->get('tags')->getData() : null;

        if ($form->isSubmitted() && $form->isValid()) {
            $this->toDo->update($toDo, $user, $categoryId, $newCategoryName, $tagsCsv);

            return $this->redirectToRoute('app_to_do_index');
        }

        return $this->render('to_do/edit.html.twig', [
            'to_do' => $toDo,
            'form'  => $form->createView(),
        ]);
    }

    /**
     * Deletes a ToDo after permission and CSRF checks.
     *
     * @param Request $request HTTP request with CSRF token.
     * @param ToDo    $toDo    ToDo being deleted.
     *
     * @return Response Redirect to index after deletion.
     */
    #[Route('/{id}', name: 'app_to_do_delete', methods: ['DELETE'], requirements: ['id' => '[1-9]\d*'])]
    public function delete(Request $request, ToDo $toDo): Response
    {
        $user = $this->getUser();
        if (!$this->toDo->canDelete($toDo, $user)) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$toDo->getId(), $request->request->get('_token'))) {
            $this->toDo->delete($toDo, $user);
        }

        return $this->redirectToRoute('app_to_do_index');
    }

    /**
     * Opens a shared ToDo via token and allows editing based on the owner's context.
     *
     * @param Request $request HTTP request with form data.
     * @param string  $token   share token.
     *
     * @return Response Rendered share form or redirect on success.
     */
    #[Route('/share/{token}', name: 'app_to_do_share', methods: ['GET', 'POST'])]
    public function share(Request $request, string $token): Response
    {
        $toDo = $this->toDo->findOneByShareToken($token);
        if (!$toDo) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ToDoForm::class, $toDo, ['user' => $toDo->getUser()]);
        $form->handleRequest($request);

        /** @var \App\Entity\Category|null $selectedCategory */
        $selectedCategory = $form->get('category')->getData();
        $categoryId       = $selectedCategory?->getId();
        $newCategoryName  = $form->get('newCategory')->getData() ?: null;
        $tagsCsv          = $form->has('tags') ? $form->get('tags')->getData() : null;

        if ($form->isSubmitted() && $form->isValid()) {
            $this->toDo->update($toDo, $toDo->getUser(), $categoryId, $newCategoryName, $tagsCsv);

            return $this->redirectToRoute('app_to_do_index');
        }

        return $this->render('to_do/share.html.twig', [
            'form'  => $form->createView(),
            'to_do' => $toDo,
        ]);
    }

    /**
     * Adds a collaborator to a ToDo by email (owner-only action).
     *
     * @param Request $request HTTP request with collaborator email.
     * @param ToDo    $toDo    ToDo to add collaborator to.
     *
     * @return Response Redirect back to edit page.
     */
    #[Route('/{id}/collaborators/add', name: 'app_to_do_collaborator_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addCollaborator(Request $request, ToDo $toDo): Response
    {
        $currentUser = $this->getUser();
        $email = (string) $request->request->get('email', '');

        try {
            $this->toDo->addCollaboratorByEmail($toDo, $email, $currentUser);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\LogicException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('app_to_do_edit', ['id' => $toDo->getId()]);
    }

    /**
     * Removes a collaborator from a ToDo (owner-only action).
     *
     * @param ToDo $toDo   ToDo to remove collaborator from.
     * @param int  $userId user id of collaborator to remove.
     *
     * @return Response Redirect back to edit page.
     */
    #[Route('/{id}/collaborators/{userId}/remove', name: 'app_to_do_collaborator_remove', methods: ['POST'], requirements: ['id' => '\d+', 'userId' => '\d+'])]
    public function removeCollaborator(ToDo $toDo, int $userId): Response
    {
        $currentUser = $this->getUser();

        try {
            $this->toDo->removeCollaboratorById($toDo, $userId, $currentUser);
        } catch (\LogicException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('app_to_do_edit', ['id' => $toDo->getId()]);
    }
}
