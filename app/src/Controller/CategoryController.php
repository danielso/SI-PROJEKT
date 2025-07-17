<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * CategoryController to manage categories for the logged-in user.
 */
#[Route('/category')]
final class CategoryController extends AbstractController
{

    /**
     * Display all categories of the logged-in user.
     *
     * @param CategoryRepository $categoryRepository
     *
     * @return Response
     */
    #[Route('/', name: 'category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        // Pobieramy aktualnie zalogowanego użytkownika
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Pobieramy kategorie tylko dla zalogowanego użytkownika
        $categories = $categoryRepository->findBy(['user' => $user]);

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    /**
     * Create a new category for the logged-in user.
     *
     * @param Request                $request
     * @param EntityManagerInterface $em
     * @param Security               $security
     *
     * @return Response
     */
    #[Route('/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, Security $security): Response
    {
        $category = new Category();

        $user = $security->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $category->setUser($user);

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Edit an existing category.
     *
     * @param Request                $request
     * @param Category               $category
     * @param EntityManagerInterface $em
     *
     * @return Response
     */
    #[Route('/{id}/edit', name: 'category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Sprawdzamy, czy użytkownik jest właścicielem kategorii
        if ($category->getUser() !== $user) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do edycji tej kategorii.');
        }

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    /**
     * Delete a category.
     *
     * @param Request                $request
     * @param Category               $category
     * @param EntityManagerInterface $em
     *
     * @return Response
     */
    #[Route('/{id}', name: 'category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($category->getUser() !== $user) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do usunięcia tej kategorii.');
        }

        // Użyjemy metody request->request->get('_token') do pobrania tokenu CSRF
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
        }

        return $this->redirectToRoute('category_index');
    }
}
