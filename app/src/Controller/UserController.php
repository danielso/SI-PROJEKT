<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;  // Importujemy EntityManagerInterface
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Controller for managing users (CRUD operations).
 */
class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;

    /**
     * UserController constructor.
     *
     * @param UserPasswordHasherInterface $passwordHasher The password hasher.
     * @param EntityManagerInterface      $entityManager  The entity manager.
     */
    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    /**
     * Displays a list of all users.
     *
     * @param UserRepository $userRepository The user repository.
     *
     * @return Response The response object.
     */
    #[Route('/admin/users', name: 'user_index')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();  // Pobranie wszystkich użytkowników

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * Edits a user.
     *
     * @param Request        $request        The HTTP request.
     * @param User           $user           The user to edit.
     * @param UserRepository $userRepository The user repository.
     *
     * @return Response The response object.
     */
    #[Route('/admin/users/{id}/edit', name: 'user_edit')]
    public function edit(Request $request, User $user, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');  // Tylko administratorzy mogą edytować

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Haszowanie hasła
            $password = $form->get('password')->getData();
            if ($password) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }

            // Pobieramy dane o blokadzie konta z formularza
            $isBlocked = $form->get('isBlocked')->getData();
            $user->setIsBlocked($isBlocked);  // Ustawiamy status blokady użytkownika

            // Zapisujemy zmiany w bazie
            $this->entityManager->flush();

            $this->addFlash('success', 'Dane użytkownika zostały zaktualizowane!');

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    /**
     * Deletes a user.
     *
     * @param Request        $request        The HTTP request.
     * @param User           $user           The user to delete.
     * @param UserRepository $userRepository The user repository.
     *
     * @return Response The response object.
     */
    #[Route('/admin/users/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');  // Tylko administratorzy mogą usuwać użytkowników

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userRepository->remove($user);
            $this->addFlash('success', 'Użytkownik został usunięty!');
        }

        return $this->redirectToRoute('user_index');
    }
}
