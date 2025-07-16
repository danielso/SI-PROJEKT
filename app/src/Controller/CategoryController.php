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

#[Route('/category')]
final class CategoryController extends AbstractController
{
    #[Route('/', name: 'category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        // Pobieramy aktualnie zalogowanego użytkownika
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');  // Jeśli użytkownik nie jest zalogowany, przekieruj na stronę logowania
        }

        // Pobieramy kategorie tylko dla zalogowanego użytkownika
        $categories = $categoryRepository->findBy(['user' => $user]);

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, Security $security): Response
    {
        $category = new Category();

        // Pobranie aktualnie zalogowanego użytkownika
        $user = $security->getUser();

        if (!$user) {
            // Jeśli użytkownik nie jest zalogowany, przekieruj na stronę logowania lub wyświetl błąd
            return $this->redirectToRoute('app_login'); // lub inna trasa do logowania
        }

        $category->setUser($user);  // Przypisanie właściciela kategorii (jeśli użytkownik jest zalogowany)

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

    #[Route('/{id}', name: 'category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        // Sprawdzamy, czy użytkownik jest właścicielem kategorii
        $user = $this->getUser();
        if ($category->getUser() !== $user) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do usunięcia tej kategorii.');
        }

        // Użyjemy metody request->request->get('_token') do pobrania tokenu CSRF
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
        }

        return $this->redirectToRoute('category_index');
    }
}
