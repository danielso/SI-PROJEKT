<?php

/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Form\TagFormType;
use App\Service\TagServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for managing tags (CRUD operations).
 */
#[Route('/tag')]
class TagController extends AbstractController
{
    /**
     * TagController constructor.
     *
     * @param TagServiceInterface $tags service handling tag operations.
     */
    public function __construct(private readonly TagServiceInterface $tags)
    {
    }

    /**
     * Displays a list of all tags with usage counts for the current user.
     *
     * @return Response Rendered list page.
     */
    #[Route('/', name: 'tag_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $rows = $this->tags->getAllWithCountsForUser($user);

        return $this->render('tag/index.html.twig', [
            'rows' => $rows,
        ]);
    }

    /**
     * Creates a new tag.
     *
     * @param Request $request HTTP request with form data.
     *
     * @return Response Rendered create form or redirect on success.
     */
    #[Route('/new', name: 'tag_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $tag  = new Tag();
        $form = $this->createForm(TagFormType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->tags->create($tag);

                return $this->redirectToRoute('tag_index');
            } catch (\InvalidArgumentException $e) {
                $form->addError(new FormError($e->getMessage()));
            }
        }

        return $this->render('tag/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Edits an existing tag.
     *
     * @param Request $request HTTP request with form data.
     * @param Tag     $tag     tag being edited.
     *
     * @return Response Rendered edit form or redirect on success.
     */
    #[Route('/{id}/edit', name: 'tag_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tag $tag): Response
    {
        $form = $this->createForm(TagFormType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->tags->update($tag);

                return $this->redirectToRoute('tag_index');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('tag/edit.html.twig', [
            'form' => $form->createView(),
            'tag'  => $tag,
        ]);
    }

    /**
     * Deletes a tag after CSRF validation.
     *
     * @param Request $request HTTP request with CSRF token.
     * @param Tag     $tag     tag being deleted.
     *
     * @return Response Redirect to index after deletion.
     */
    #[Route('/{id}', name: 'tag_delete', methods: ['DELETE'])]
    public function delete(Request $request, Tag $tag): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tag->getId(), $request->request->get('_token'))) {
            $this->tags->delete($tag);
        }

        return $this->redirectToRoute('tag_index');
    }
}
