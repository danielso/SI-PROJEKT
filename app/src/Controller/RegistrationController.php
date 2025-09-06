<?php

/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\RegisterServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for user registration and related functionalities.
 */
class RegistrationController extends AbstractController
{
    /**
     * Handles user registration.
     *
     * @param Request                  $request  HTTP request
     * @param RegisterServiceInterface $register domain service handling registration flow
     *
     * @return Response Rendered registration form or redirect on success
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, RegisterServiceInterface $register): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            $register->register($user, $plainPassword);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
