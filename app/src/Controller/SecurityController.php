<?php

// src/Controller/SecurityController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

/**
 * Controller for handling user authentication (login and logout).
 */
class SecurityController extends AbstractController
{
    private $userRepository;

    /**
     * SecurityController constructor.
     *
     * @param UserRepository $userRepository The user repository.
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Handles user login.
     *
     * @param AuthenticationUtils $authenticationUtils The authentication utils service.
     *
     * @return Response The response object.
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('home');
        }

        $lastUsername = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();

        // Sprawdzenie, czy zablokowany
        if ($error instanceof CustomUserMessageAuthenticationException) {
            $this->addFlash('error', $error->getMessage());
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Handles user logout.
     *
     * @throws \LogicException Always thrown as Symfony intercepts the logout process.
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Ta metoda może być pusta, ponieważ będzie przechwycona przez Symfony
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
