<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Controller for the home page, accessible only to users with the ROLE_USER.
 * It checks if the user has the ROLE_ADMIN and passes this information to the view.
 */
#[IsGranted('ROLE_USER')]
class HomeController extends AbstractController
{
    /**
     * Displays the home page and checks if the logged-in user is an administrator.
     *
     * @return Response
     */
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // Sprawdzamy, czy użytkownik jest administratorem
        $isAdmin = in_array('ROLE_ADMIN', $this->getUser()->getRoles());

        return $this->render('home/home.html.twig', [
            'is_admin' => $isAdmin,
        ]);
    }
}
