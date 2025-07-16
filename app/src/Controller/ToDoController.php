<?php

namespace App\Controller;

use App\Entity\ToDo;
use App\Entity\Tag;
use App\Repository\ToDoRepository;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ToDoForm;

class ToDoController extends AbstractController
{
    private $toDoRepository;

    public function __construct(ToDoRepository $toDoRepository)
    {
        $this->toDoRepository = $toDoRepository;
    }

    #[Route('/to/do', name: 'app_to_do_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository, TagRepository $tagRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        //QueryBuildera
        $qb = $this->toDoRepository->createQueryBuilder('t')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.tags', 'tag')
            ->addSelect('c', 'tag')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC');

        // Pobieramy parametry z URL
        $categoryId = $request->query->get('category');
        $tagId = $request->query->get('tag');
        $searchTerm = $request->query->get('search');  //fraza wyszukiwania

        // Filtracja po kategorii
        if ($categoryId) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        // Filtracja po tagu
        if ($tagId) {
            $qb->leftJoin('t.tags', 'tag')
                ->andWhere('tag.id = :tagId')
                ->setParameter('tagId', $tagId);
        }

        // Filtracja po wyszukiwanie w tytule i treści
        if ($searchTerm) {
            $qb->andWhere('LOWER(t.title) LIKE :searchTerm OR LOWER(t.content) LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');
        }

        $toDoList = $qb->getQuery()->getResult();

        $categories = $categoryRepository->findBy(['user' => $user]);
        $tags = $tagRepository->findAll();

        // Przekazujemy dane do widoku
        return $this->render('to_do/index.html.twig', [
            'to_do' => $toDoList,
            'categories' => $categories,
            'tags' => $tagRepository->findAll(),
            'selectedCategory' => $categoryId,
            'selectedTag' => $tagId,
            'searchTerm' => $searchTerm,
        ]);
    }



    #[Route('/to/do/new', name: 'app_to_do_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $toDo = new ToDo();
        $form = $this->createForm(ToDoForm::class, $toDo, [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        // Przetwarzanie kategorii
        $categoryName = $request->get('category');
        $newCategoryName = $request->get('newCategory');

        if ($categoryName) {
            $category = $categoryRepository->find($categoryName);
            if ($category) {
                $toDo->setCategory($category);
            }
        } elseif ($newCategoryName) {
            $category = new Category();
            $category->setName($newCategoryName);
            $category->setUser($user);
            $entityManager->persist($category);
            $entityManager->flush();
            $toDo->setCategory($category);
        }

        // Przetwarzanie tagów
        $tagsString = $form->get('tags')->getData();
        if ($tagsString) {
            $tagNames = array_map('trim', explode(',', $tagsString));
            foreach ($tagNames as $tagName) {
                if (!$tagName) continue;

                $tag = $entityManager->getRepository(Tag::class)->findOneBy(['name' => $tagName]);
                if (!$tag) {
                    $tag = new Tag();
                    $tag->setName($tagName);
                    $entityManager->persist($tag);
                }
                $toDo->addTag($tag);
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $toDo->setShareToken(bin2hex(random_bytes(16)));  // Generowanie tokenu


            $toDo->setUser($user);

            $entityManager->persist($toDo);
            $entityManager->flush();

            return $this->redirectToRoute('app_to_do_index');
        }

        return $this->render('to_do/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/to/do/{id}', name: 'app_to_do_show')]
    public function show(ToDo $toDo): Response
    {
        return $this->render('to_do/show.html.twig', [
            'to_do' => $toDo,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_to_do_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ToDo $toDo, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($toDo->getUser() !== $user) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do edycji tego zadania.');
        }

        $form = $this->createForm(ToDoForm::class, $toDo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_to_do_index');
        }

        return $this->render('to_do/edit.html.twig', [
            'to_do' => $toDo,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_to_do_delete', methods: ['POST'])]
    public function delete(Request $request, ToDo $toDo, EntityManagerInterface $entityManager): Response
    {

        $user = $this->getUser();
        if ($toDo->getUser() !== $user) {
            throw $this->createAccessDeniedException('Nie masz uprawnień do usunięcia tego zadania.');
        }

        if ($this->isCsrfTokenValid('delete' . $toDo->getId(), $request->request->get('_token'))) {
            $entityManager->remove($toDo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_to_do_index');
    }

    #[Route('/to/do/share/{token}', name: 'app_to_do_share', methods: ['GET', 'POST'])]
    public function share(Request $request, string $token, ToDoRepository $toDoRepository, EntityManagerInterface $entityManager): Response
    {
        // Pobierz zadanie na podstawie tokenu
        $toDo = $toDoRepository->findOneBy(['shareToken' => $token]);

        if (!$toDo) {
            throw $this->createNotFoundException('Zadanie nie zostało znalezione.');
        }

        // Jeśli użytkownik chce edytować zadanie
        $form = $this->createForm(ToDoForm::class, $toDo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_to_do_index');
        }

        return $this->render('to_do/share.html.twig', [
            'form' => $form->createView(),
            'to_do' => $toDo,
        ]);
    }
}
