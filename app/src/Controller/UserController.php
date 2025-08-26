<?php
/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\UserRepository;
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
     * @param UserServiceInterface $userService Service handling user admin logic.
     */
    public function __construct(private readonly UserServiceInterface $userService)
    {
    }

    /**
     * Displays a list of all users.
     *
     * @param UserRepository $userRepository
     *
     * @return Response
     */
    #[Route('/admin/users', name: 'user_index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    /**
     * Edits a user (admin only).
     *
     * @param Request        $request
     * @param User           $user
     * @param UserRepository $userRepository
     *
     * @return Response
     */
    #[Route('/admin/users/{id}/edit', name: 'user_edit')]
    public function edit(Request $request, User $user, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $wasAdmin = $user->hasRole('ROLE_ADMIN');

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            $isBlocked   = $form->has('isBlocked') ? (bool) $form->get('isBlocked')->getData() : null;

            try {
                $this->userService->update($user, $wasAdmin, $newPassword ?: null, $isBlocked);
                $this->addFlash('success', 'Dane użytkownika zostały zaktualizowane!');

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
     * @param Request $request
     * @param User    $user
     *
     * @return Response
     */
    #[Route('/admin/users/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            try {
                $this->userService->delete($user);
                $this->addFlash('success', 'Użytkownik został usunięty!');
            } catch (\LogicException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->redirectToRoute('user_index');
    }
}
