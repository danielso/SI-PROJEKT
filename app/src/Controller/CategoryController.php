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
use App\Security\Voter\CategoryVoter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller responsible for Category CRUD actions using the service layer.
 */
#[Route('/category')]
final class CategoryController extends AbstractController
{
    /**
     * CategoryController constructor.
     *
     * @param CategoryServiceInterface $categoryService Service handling category operations
     */
    public function __construct(private readonly CategoryServiceInterface $categoryService)
    {
    }

    /**
     * Displays a list of categories for the current user.
     *
     * @return Response Rendered categories list page
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
     * @param Request $request HTTP request with form data
     *
     * @return Response Rendered form or redirect to index on success
     */
    #[Route('/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $category = new Category();
        $category->setUser($user);

        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->categoryService->create($category, $user);

                return $this->redirectToRoute('category_index');
            } catch (\InvalidArgumentException $e) {
                $form->addError(new \Symfony\Component\Form\FormError($e->getMessage()));
            }
        }

        $status = $form->isSubmitted() && !$form->isValid() ? 422 : 200;

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ], new Response('', $status));
    }

    /**
     * Edits an existing category.
     *
     * @param Request  $request  HTTP request with form data
     * @param Category $category Category being edited
     *
     * @return Response Rendered form or redirect to index on success
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
     * @param Request             $request    HTTP request with CSRF token
     * @param Category            $category   Category being deleted
     * @param TranslatorInterface $translator Translator for flash messages
     *
     * @return Response Redirect to categories index
     */
    #[Route('/{id}/delete', name: 'category_delete', requirements: ['id' => '[1-9]\d*'], methods: ['GET', 'DELETE'])]
    public function delete(Request $request, Category $category, TranslatorInterface $translator): Response
    {
        if (!$this->isGranted(CategoryVoter::DELETE, $category)) {
            $this->addFlash('warning', $translator->trans('message.record_not_found'));

            return $this->redirectToRoute('category_index');
        }

        if (!$this->categoryService->canBeDeleted($category)) {
            $this->addFlash('warning', $translator->trans('message.category_contains_items'));

            return $this->redirectToRoute('category_index');
        }

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('category_delete', ['id' => $category->getId()]))
            ->setMethod('DELETE')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login');
            }

            $this->categoryService->delete($category, $user);
            $this->addFlash('success', $translator->trans('message.deleted_successfully'));

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/delete.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }
}
