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
     * @param ToDoServiceInterface $toDo Service handling ToDo operations.
     */
    public function __construct(private readonly ToDoServiceInterface $toDo)
    {
    }

    /**
     * Lists ToDo items for the current user with optional filters.
     *
     * @param Request $request
     *
     * @return Response
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
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/new', name: 'app_to_do_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $toDo = new ToDo();
        $form = $this->createForm(ToDoForm::class, $toDo, ['user' => $user]);
        $form->handleRequest($request);

        $categoryId      = $request->get('category') ? (int) $request->get('category') : null;
        $newCategoryName = $request->get('newCategory') ?: null;
        $tagsCsv         = $form->get('tags')->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->toDo->create($toDo, $user, $categoryId, $newCategoryName, $tagsCsv);

            return $this->redirectToRoute('app_to_do_index');
        }

        return $this->render('to_do/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Shows a single ToDo if the current user can view it.
     *
     * @param ToDo $toDo
     *
     * @return Response
     */
    #[Route('/{id}', name: 'app_to_do_show', methods: ['GET'])]
    public function show(ToDo $toDo): Response
    {
        $user = $this->getUser();
        if (!$this->toDo->canView($toDo, $user)) {
            throw $this->createAccessDeniedException('Brak dostępu do tego zadania.');
        }

        return $this->render('to_do/show.html.twig', [
            'to_do' => $toDo,
        ]);
    }

    /**
     * Edits an existing ToDo if the current user can edit it.
     *
     * @param Request $request
     * @param ToDo    $toDo
     *
     * @return Response
     */
    #[Route('/{id}/edit', name: 'app_to_do_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ToDo $toDo): Response
    {
        $user = $this->getUser();
        if (!$this->toDo->canEdit($toDo, $user)) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do edycji tego zadania.');
        }

        $form = $this->createForm(ToDoForm::class, $toDo, ['user' => $user]);
        $form->handleRequest($request);

        $categoryId      = $request->get('category') ? (int) $request->get('category') : null;
        $newCategoryName = $request->get('newCategory') ?: null;
        $tagsCsv         = $form->has('tags') ? $form->get('tags')->getData() : null;

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
     * @param Request $request
     * @param ToDo    $toDo
     *
     * @return Response
     */
    #[Route('/{id}', name: 'app_to_do_delete', methods: ['POST'])]
    public function delete(Request $request, ToDo $toDo): Response
    {
        $user = $this->getUser();
        if (!$this->toDo->canDelete($toDo, $user)) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do usunięcia tego zadania.');
        }

        if ($this->isCsrfTokenValid('delete'.$toDo->getId(), $request->request->get('_token'))) {
            $this->toDo->delete($toDo, $user);
        }

        return $this->redirectToRoute('app_to_do_index');
    }

    /**
     * Opens a shared ToDo via token and allows editing based on the owner's context.
     *
     * @param Request $request
     * @param string  $token
     *
     * @return Response
     */
    #[Route('/share/{token}', name: 'app_to_do_share', methods: ['GET', 'POST'])]
    public function share(Request $request, string $token): Response
    {
        $toDo = $this->toDo->findOneByShareToken($token);
        if (!$toDo) {
            throw $this->createNotFoundException('Zadanie nie zostało znalezione.');
        }

        $form = $this->createForm(ToDoForm::class, $toDo, ['user' => $toDo->getUser()]);
        $form->handleRequest($request);

        $categoryId      = $request->get('category') ? (int) $request->get('category') : null;
        $newCategoryName = $request->get('newCategory') ?: null;
        $tagsCsv         = $form->has('tags') ? $form->get('tags')->getData() : null;

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
     * @param Request $request
     * @param ToDo    $toDo
     *
     * @return Response
     */
    #[Route('/{id}/collaborators/add', name: 'app_to_do_collaborator_add', methods: ['POST'])]
    public function addCollaborator(Request $request, ToDo $toDo): Response
    {
        $currentUser = $this->getUser();
        $email = (string) $request->request->get('email', '');

        try {
            $this->toDo->addCollaboratorByEmail($toDo, $email, $currentUser);
            $this->addFlash('success', 'Współpracownik dodany.');
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
     * @param ToDo $toDo
     * @param int  $userId
     *
     * @return Response
     */
    #[Route('/{id}/collaborators/{userId}/remove', name: 'app_to_do_collaborator_remove', methods: ['POST'])]
    public function removeCollaborator(ToDo $toDo, int $userId): Response
    {
        $currentUser = $this->getUser();

        try {
            $this->toDo->removeCollaboratorById($toDo, $userId, $currentUser);
            $this->addFlash('success', 'Współpracownik usunięty.');
        } catch (\LogicException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('app_to_do_edit', ['id' => $toDo->getId()]);
    }
}
