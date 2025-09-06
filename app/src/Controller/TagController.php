<?php

/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Form\TagFormType;
use App\Security\Voter\TagVoter;
use App\Service\TagServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for managing tags (CRUD operations).
 */
#[Route('/tag')]
class TagController extends AbstractController
{
    /**
     * TagController constructor.
     *
     * @param \App\Service\TagServiceInterface $tags service handling tag operations
     */
    public function __construct(private readonly TagServiceInterface $tags)
    {
    }

    /**
     * Displays a list of all tags with usage counts for the current user.
     *
     * @return \Symfony\Component\HttpFoundation\Response Rendered list page
     */
    #[Route('/', name: 'tag_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var \App\Entity\User|null $user */
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
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP request with form data
     *
     * @return \Symfony\Component\HttpFoundation\Response Rendered create form or redirect on success
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
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP request with form data
     * @param \App\Entity\Tag                           $tag     tag being edited
     *
     * @return \Symfony\Component\HttpFoundation\Response Rendered edit form or redirect on success
     */
    #[Route('/{id}/edit', name: 'tag_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tag $tag): Response
    {
        $this->denyAccessUnlessGranted(TagVoter::EDIT, $tag);

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
     * @param \Symfony\Component\HttpFoundation\Request          $request    HTTP request with CSRF token
     * @param \App\Entity\Tag                                    $tag        tag being deleted
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translator translator for flash messages
     *
     * @return \Symfony\Component\HttpFoundation\Response Redirect to index after deletion
     */
    #[Route('/{id}/delete', name: 'tag_delete', methods: ['GET', 'DELETE'], requirements: ['id' => '[1-9]\d*'])]
    public function delete(Request $request, Tag $tag, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted(TagVoter::DELETE, $tag);

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('tag_delete', ['id' => $tag->getId()]))
            ->setMethod('DELETE')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tags->delete($tag);
            $this->addFlash('success', $translator->trans('message.deleted_successfully'));

            return $this->redirectToRoute('tag_index');
        }

        return $this->render('tag/delete.html.twig', [
            'form' => $form->createView(),
            'tag'  => $tag,
        ]);
    }
}
