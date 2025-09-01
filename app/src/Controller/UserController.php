<?php

/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\UserFormType;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\RegisterServiceInterface;
use App\Service\UserServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for managing users (admin CRUD operations).
 */
class UserController extends AbstractController
{
    /**
     * UserController constructor.
     *
     * @param UserServiceInterface $userService service handling user admin logic.
     */
    public function __construct(private readonly UserServiceInterface $userService)
    {
    }

    /**
     * Displays a list of all users (admin only).
     *
     * @return Response Rendered list page.
     */
    #[Route('/admin/users', name: 'user_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('user/index.html.twig', [
            'users' => $this->userService->listAll(),
        ]);
    }

    /**
     * Creates a new user (admin only).
     *
     * @param Request                  $request  HTTP request with form data.
     * @param RegisterServiceInterface $register registration domain service.
     *
     * @return Response Rendered create form or redirect on success.
     */
    #[Route('/admin/users/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, RegisterServiceInterface $register): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = (string) $form->get('plainPassword')->getData();
            $register->register($user, $plain);

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Edits a user (admin only).
     *
     * @param Request        $request        HTTP request with form data.
     * @param User           $user           user being edited.
     * @param UserRepository $userRepository user repository.
     *
     * @return Response Rendered edit form or redirect on success.
     */
    #[Route('/admin/users/{id}/edit', name: 'user_edit')]
    public function edit(Request $request, User $user, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $wasAdmin = $user->hasRole('ROLE_ADMIN');

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isBlocked = $form->has('isBlocked') ? (bool) $form->get('isBlocked')->getData() : null;

            try {
                $this->userService->update($user, $wasAdmin, null, $isBlocked);

                return $this->redirectToRoute('user_index');
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('user_edit', ['id' => $user->getId()]);
            }
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * Deletes a user (admin only) after CSRF validation.
     *
     * @param Request $request HTTP request with CSRF token.
     * @param User    $user    user being deleted.
     *
     * @return Response Redirect to index after deletion.
     */
    #[Route('/admin/users/{id}/delete', name: 'user_delete', methods: ['DELETE'])]
    public function delete(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            try {
                $this->userService->delete($user);
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * Changes a user's password (admin only).
     *
     * @param Request $request HTTP request with form data.
     * @param User    $user    target user entity.
     *
     * @return Response Rendered password form or redirect on success.
     */
    #[Route('/admin/users/{id}/password', name: 'user_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = (string) $form->get('newPassword')->getData();

            return $this->redirectToRoute('user_edit', ['id' => $user->getId()]);
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
