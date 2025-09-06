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
use App\Security\Voter\ToDoVoter;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

/**
 * Controller for managing ToDo items (listing, CRUD, sharing, collaborators).
 */
#[Route('/to/do')]
final class ToDoController extends AbstractController
{
    /**
     * ToDoController constructor.
     *
     * @param ToDoServiceInterface $toDo Service handling ToDo operations
     */
    public function __construct(private readonly ToDoServiceInterface $toDo)
    {
    }

    /**
     * Lists ToDo items for the current user with optional filters.
     *
     * @param Request $request HTTP request with query parameters
     *
     * @return Response Rendered list page
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
     * @param Request $request HTTP request with form data
     *
     * @return Response Rendered create form or redirect on success
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

        $categoryName = $form->get('categoryName')->getData() ?: null;
        $tagsCsv      = $form->get('tags')->getData() ?: null;

        if ($form->isSubmitted() && $form->isValid()) {
            $this->toDo->create($toDo, $user, $categoryName, $tagsCsv);

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
     * @param ToDo $toDo ToDo to display
     *
     * @return Response Rendered details page
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException When access is denied
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
     * @param Request $request HTTP request with form data
     * @param ToDo    $toDo    ToDo being edited
     *
     * @return Response Rendered edit form or redirect on success
     */
    #[Route('/{id}/edit', name: 'app_to_do_edit', methods: ['GET', 'POST'], requirements: ['id' => '[1-9]\d*'])]
    public function edit(Request $request, ToDo $toDo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted(ToDoVoter::EDIT, $toDo);

        $form = $this->createForm(ToDoForm::class, $toDo, ['user' => $user]);
        $form->handleRequest($request);

        $categoryName = $form->get('categoryName')->getData() ?: null;
        $tagsCsv      = $form->has('tags') ? $form->get('tags')->getData() : null;

        if ($form->isSubmitted() && $form->isValid()) {
            $this->toDo->update($toDo, $user, $categoryName, $tagsCsv);

            return $this->redirectToRoute('app_to_do_index');
        }

        $addCollabForm = $this->createFormBuilder(null, [
            'action' => $this->generateUrl('app_to_do_collaborator_add', ['id' => $toDo->getId()]),
            'method' => 'POST',
            'translation_domain' => 'messages',
        ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'todo.collaborators.email_label',
                'attr' => [
                    'placeholder' => 'todo.collaborators.email_placeholder',
                ],
            ])
            ->getForm();

        $removeForms = [];
        foreach ($toDo->getCollaborators() as $u) {
            $removeForms[$u->getId()] = $this->createFormBuilder(null, [
                'action' => $this->generateUrl('app_to_do_collaborator_remove', ['id' => $toDo->getId(), 'userId' => $u->getId()]),
                'method' => 'POST',
            ])
                ->getForm()
                ->createView();
        }

        return $this->render('to_do/edit.html.twig', [
            'to_do'           => $toDo,
            'form'            => $form->createView(),
            'add_collab_form' => $addCollabForm->createView(),
            'remove_forms'    => $removeForms,
        ]);
    }

    /**
     * Deletes a ToDo after permission and CSRF checks.
     *
     * @param Request             $request    HTTP request with CSRF token
     * @param ToDo                $toDo       ToDo being deleted
     * @param TranslatorInterface $translator Translator for flash messages
     *
     * @return Response Redirect to index after deletion
     */
    #[Route('/{id}/delete', name: 'app_to_do_delete', methods: ['GET', 'DELETE'], requirements: ['id' => '[1-9]\d*'])]
    public function delete(Request $request, ToDo $toDo, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted(ToDoVoter::DELETE, $toDo);

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('app_to_do_delete', ['id' => $toDo->getId()]))
            ->setMethod('DELETE')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->toDo->delete($toDo, $user);
            $this->addFlash('success', $translator->trans('message.deleted_successfully'));

            return $this->redirectToRoute('app_to_do_index');
        }

        return $this->render('to_do/delete.html.twig', [
            'form' => $form->createView(),
            'to_do' => $toDo,
        ]);
    }

    /**
     * Opens a shared ToDo via token and allows editing based on the owner's context.
     *
     * @param Request $request HTTP request with form data
     * @param string  $token   Share token
     *
     * @return Response Rendered share form or redirect on success
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException When the token is invalid
     */
    #[Route('/share/{token}', name: 'app_to_do_share', methods: ['GET', 'POST'])]
    public function share(Request $request, string $token): Response
    {
        $toDo = $this->toDo->findOneByShareToken($token);
        if (null === $toDo) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ToDoForm::class, $toDo, ['user' => $toDo->getUser()]);
        $form->handleRequest($request);

        $categoryName = $form->get('categoryName')->getData() ?: null;
        $tagsCsv      = $form->has('tags') ? $form->get('tags')->getData() : null;

        if ($form->isSubmitted() && $form->isValid()) {
            $this->toDo->update($toDo, $toDo->getUser(), $categoryName, $tagsCsv);

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
     * @param Request $request HTTP request with collaborator email
     * @param ToDo    $toDo    ToDo to add collaborator to
     *
     * @return Response Redirect back to edit page
     */
    #[Route('/{id}/collaborators/add', name: 'app_to_do_collaborator_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addCollaborator(Request $request, ToDo $toDo): Response
    {
        $this->denyAccessUnlessGranted(ToDoVoter::COLLAB_MANAGE, $toDo);

        $currentUser = $this->getUser();

        $form = $this->createFormBuilder(null, [
            'action' => $this->generateUrl('app_to_do_collaborator_add', ['id' => $toDo->getId()]),
            'method' => 'POST',
            'translation_domain' => 'messages',
        ])
            ->add('email', EmailType::class, [
                'required' => true,
                'label' => 'todo.collaborators.email_label',
            ])
            ->getForm();


        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $form->get('email')->getData();
            try {
                $this->toDo->addCollaboratorByEmail($toDo, $email, $currentUser);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\LogicException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }
        }

        return $this->redirectToRoute('app_to_do_edit', ['id' => $toDo->getId()]);
    }

    /**
     * Removes a collaborator from a ToDo (owner-only action).
     *
     * @param Request $request HTTP request carrying CSRF token
     * @param ToDo    $toDo    ToDo to remove collaborator from
     * @param int     $userId  User id of collaborator to remove
     *
     * @return Response Redirect back to edit page
     */
    #[Route('/{id}/collaborators/{userId}/remove', name: 'app_to_do_collaborator_remove', methods: ['POST'], requirements: ['id' => '\d+', 'userId' => '\d+'])]
    public function removeCollaborator(Request $request, ToDo $toDo, int $userId): Response
    {
        $this->denyAccessUnlessGranted(ToDoVoter::COLLAB_MANAGE, $toDo);

        $currentUser = $this->getUser();

        $form = $this->createFormBuilder(null, [
            'action' => $this->generateUrl('app_to_do_collaborator_remove', ['id' => $toDo->getId(), 'userId' => $userId]),
            'method' => 'POST',
        ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->toDo->removeCollaboratorById($toDo, $userId, $currentUser);
            } catch (\LogicException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }
        }

        return $this->redirectToRoute('app_to_do_edit', ['id' => $toDo->getId()]);
    }
}
