<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminProfileFullType;
use App\Form\AdminPasswordChangeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Controller for managing admin profile and password change functionalities.
 */
class ProfileController extends AbstractController
{
    /**
     * Edits the admin profile.
     *
     * @param Request $request                   The HTTP request.
     * @param EntityManagerInterface $em         The entity manager.
     * @param UserPasswordHasherInterface $hasher The password hasher.
     *
     * @return Response
     */
    #[Route('/profile/edit', name: 'profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(AdminProfileFullType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            if ($newPassword) {
                $hashedPassword = $hasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            }

            $em->flush();

            $this->addFlash('success', 'Dane zostały zaktualizowane.');

            return $this->redirectToRoute('home');
        }

        return $this->render('profile/profile_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    /**
     * Changes the admin password.
     *
     * @param Request $request                   The HTTP request.
     * @param UserPasswordHasherInterface $passwordHasher The password hasher.
     * @param EntityManagerInterface $em         The entity manager.
     *
     * @return Response
     */
    #[Route('/admin/change-password', name: 'admin_change_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(AdminPasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Hasło zostało zmienione.');

            // Add blank line before return statement
            return $this->redirectToRoute('admin_change_password');
        }

        return $this->render('admin/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
