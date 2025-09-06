<?php

/**
 * @license MIT
 */

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @return Response Rendered home page
     */
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $isAdmin = in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true);

        return $this->render('home/home.html.twig', [
            'is_admin' => $isAdmin,
        ]);
    }
}
