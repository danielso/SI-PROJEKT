<?php

/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminPasswordChangeType;
use App\Form\AdminProfileFullType;
use App\Service\ProfileServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller responsible for profile management.
 */
class ProfileController extends AbstractController
{
    /**
     * ProfileController constructor.
     *
     * @param ProfileServiceInterface $profile Service handling profile operations
     */
    public function __construct(private readonly ProfileServiceInterface $profile)
    {
    }

    /**
     * Displays and processes the profile edit form for the currently logged-in user.
     *
     * Requires ROLE_USER.
     *
     * @param Request $request HTTP request
     *
     * @return Response Rendered edit form or redirect on success
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/profile/edit', name: 'profile_edit')]
    public function editProfile(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(AdminProfileFullType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $this->profile->updateProfile($user, $newPassword ?: null);

            return $this->redirectToRoute('home');
        }

        return $this->render('profile/profile_edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays and processes the admin password change form.
     *
     * Requires ROLE_ADMIN.
     *
     * @param Request $request HTTP request
     *
     * @return Response Rendered password form or redirect on success
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/change-password', name: 'admin_change_password')]
    public function changePassword(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(AdminPasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $this->profile->changePassword($user, $newPassword);

            return $this->redirectToRoute('admin_change_password');
        }

        return $this->render('admin/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
