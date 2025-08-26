<?php
/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Service\CategoryServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller responsible for Category CRUD actions using the service layer.
 */
#[Route('/category')]
final class CategoryController extends AbstractController
{
    /**
     * CategoryController constructor.
     *
     * @param CategoryServiceInterface $categoryService Service handling category operations.
     */
    public function __construct(private readonly CategoryServiceInterface $categoryService)
    {
    }

    /**
     * Displays a list of categories for the current user.
     *
     * @return Response
     */
    #[Route('/', name: 'category_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $rows = $this->categoryService->getListForUserWithCounts($user);

        return $this->render('category/index.html.twig', [
            'rows' => $rows,
        ]);
    }

    /**
     * Creates a new category for the current user.
     *
     * @param Request $request
     *
     * @return Response
     */
    #[Route('/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $category = new Category();
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryService->create($category, $user);

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Edits an existing category.
     *
     * @param Request  $request
     * @param Category $category
     *
     * @return Response
     */
    #[Route('/{id}/edit', name: 'category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->categoryService->update($category, $user);

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    /**
     * Deletes a category after CSRF validation.
     *
     * @param Request  $request
     * @param Category $category
     *
     * @return Response
     */
    #[Route('/{id}', name: 'category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $this->categoryService->delete($category, $user);
        }

        return $this->redirectToRoute('category_index');
    }
}
