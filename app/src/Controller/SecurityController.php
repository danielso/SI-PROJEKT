<?php

/**
 * @license MIT
 */

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
     * @param UserRepository $userRepository the user repository.
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Handles user login.
     *
     * @param AuthenticationUtils $authenticationUtils the authentication utils service.
     *
     * @return Response Rendered login form or redirect on success.
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('home');
        }

        $lastUsername = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();

        // Sprawdzenie czy zablokowany
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
     * @throws \LogicException always thrown as Symfony intercepts the logout process.
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException();
    }
}
