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
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

/**
 * Controller for handling user authentication (login and logout).
 */
class SecurityController extends AbstractController
{
    /**
     * Handles user login.
     *
     * @param AuthenticationUtils $authenticationUtils the authentication utils service
     *
     * @return Response Rendered login form or redirect on success
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('home');
        }

        $lastUsername = $authenticationUtils->getLastUsername();
        $error = $authenticationUtils->getLastAuthenticationError();

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
     * @throws \LogicException always thrown as Symfony intercepts the logout process
     */
    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): void
    {
    }
}
