<?php

/**
 * @license MIT
 */

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\User;
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
     * @param TagServiceInterface $tags Service handling tag operations
     */
    public function __construct(private readonly TagServiceInterface $tags)
    {
    }

    /**
     * Displays a list of all tags with usage counts for the current user.
     *
     * @return Response Rendered list page
     */
    #[Route('/', name: 'tag_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User|null $user */
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
     * @param Request $request HTTP request with form data
     *
     * @return Response Rendered create form or redirect on success
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
     * @param Request $request HTTP request with form data
     * @param Tag     $tag     Tag being edited
     *
     * @return Response Rendered edit form or redirect on success
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
     * @param Request             $request    HTTP request with CSRF token
     * @param Tag                 $tag        Tag being deleted
     * @param TranslatorInterface $translator Translator for flash messages
     *
     * @return Response Redirect to index after deletion
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
