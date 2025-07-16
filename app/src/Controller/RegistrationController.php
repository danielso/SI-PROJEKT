<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Zapisujemy dane z formularza, aby ustawić hasło
            $plainPassword = $form->get('plainPassword')->getData(); // Pobieramy hasło z formularza
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword); // Hashujemy hasło
            $user->setPassword($hashedPassword);  // Ustawiamy hasło użytkownika
            $user->setRoles(['ROLE_USER']);  // Nadawanie domyślnej roli użytkownika

            // Zapisujemy użytkownika w bazie danych
            $entityManager->persist($user);
            $entityManager->flush();

            // Przekierowanie na stronę logowania po udanej rejestracji
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
