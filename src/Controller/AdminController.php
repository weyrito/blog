<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;
use App\Entity\Category;
use App\Entity\Comment;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $posts = $entityManager->getRepository(Post::class)->findAll();
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $comments = $entityManager->getRepository(Comment::class)->findAll();
        
        return $this->render('admin/dashboard.html.twig', [
            'posts' => $posts,
            'categories' => $categories,
            'comments' => $comments,
        ]);
    }


    #[Route('/admin/comments/approve/{id}', name: 'admin_approve_comment')]
    public function approveComment(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $comment->setStatus('visible');
        $entityManager->flush();

        $this->addFlash('success', 'Le commentaire a été approuvé avec succès.');

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/comments/reject/{id}', name: 'admin_reject_comment')]
    public function rejectComment(Comment $comment, EntityManagerInterface $entityManager): Response
    {
        $comment->setStatus('invisible');
        $entityManager->flush();

        $this->addFlash('success', 'Le commentaire a été rejeté.');

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/users', name: 'users')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/users/verify/{id}', name: 'verify_user')]
    public function verifyUser(User $user = null, EntityManagerInterface $entityManager): Response
    {
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        $user->setVerified(true);
        $entityManager->flush();

        $this->addFlash('success', 'L\'utilisateur a été vérifié avec succès.');

        return $this->redirectToRoute('users');
    }

    #[Route('/users/invalidate/{id}', name: 'admin_invalidate_user')]
    public function invalidateUser(User $user = null, EntityManagerInterface $entityManager): Response
    {
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        $user->setVerified(false);
        $entityManager->flush();

        $this->addFlash('success', 'L\'utilisateur a été invalidé avec succès.');

        return $this->redirectToRoute('users');
    }

    #[Route('/users/update-role/{id}', name: 'admin_update_user_role')]
    public function updateUserRole(User $user = null, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        $role = $request->request->get('role');
        if (!$role) {
            $this->addFlash('error', 'Aucun rôle spécifié.');
            return $this->redirectToRoute('users');
        }

        $user->setRoles([$role]);
        $entityManager->flush();

        $this->addFlash('success', 'Le rôle de l\'utilisateur a été mis à jour.');

        return $this->redirectToRoute('users');
    }

    #[Route('/users/delete/{id}', name: 'admin_delete_user')]
    public function deleteUser(User $user = null, EntityManagerInterface $entityManager): Response
    {
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        // Supprimer les posts de l'utilisateur
        foreach ($user->getPost() as $post) {
            $entityManager->remove($post);
        }

        // Supprimer l'utilisateur
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');

        return $this->redirectToRoute('users');
    }

    #[Route('/categories', name: 'admin_categories')]
    public function listCategories(EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }
}
