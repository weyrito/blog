<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Category;
use App\Entity\Post;
use App\Form\CategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'category_index')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/categories/new', name: 'category_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a été créée avec succès.');

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/categories/edit/{id}', name: 'category_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Category $category = null, EntityManagerInterface $entityManager): Response
    {
        if (!$category) {
            throw new NotFoundHttpException('Catégorie introuvable.');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a été mise à jour avec succès.');

            return $this->redirectToRoute('category_index');
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/categories/delete/{id}', name: 'category_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Category $category = null, EntityManagerInterface $entityManager): Response
    {
        if (!$category) {
            throw new NotFoundHttpException('Catégorie introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a été supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Le jeton CSRF est invalide. La catégorie n\'a pas été supprimée.');
        }

        return $this->redirectToRoute('category_index');
    }

    #[Route('/category/{id}/posts', name: 'posts_by_category')]
    #[IsGranted('ROLE_USER')]
    public function postsByCategory(Category $category = null, EntityManagerInterface $entityManager): Response
    {
        if (!$category) {
            throw new NotFoundHttpException('Catégorie introuvable.');
        }

        $posts = $entityManager->getRepository(Post::class)->findBy(['Category' => $category]);

        return $this->render('category/post.html.twig', [
            'posts' => $posts,
            'category' => $category,
        ]);
    }
}
