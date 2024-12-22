<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    #[Route('/posts/{id}', name: 'post_show')]
    #[IsGranted('ROLE_USER')]
    public function show(Post $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$post) {
            throw $this->createNotFoundException('L\'article demandé n\'existe pas.');
        }

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTime());
            $comment->setUser($this->getUser());
            $comment->setPost($post);
            $comment->setStatus('en attente'); 
    
            $entityManager->persist($comment);
            $entityManager->flush();
    
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès.');
    
            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'comment_form' => $form->createView(),
        ]);
    }

    #[Route('/posts/new', name: 'post_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $post->setPublishedAt(new \DateTime());

            $pictureFile = $form->get('picture')->getData();
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pictureFile->guessExtension();

                try {
                    $pictureFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                }

                $post->setPicture($newFilename);
            }

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'L\'article a été créé avec succès.');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/posts/edit/{id}', name: 'post_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Post $post = null, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        if (!$post) {
            throw $this->createNotFoundException('L\'article demandé n\'existe pas.');
        }

        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pictureFile = $form->get('picture')->getData();
            if ($pictureFile) {
                $originalFilename = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $pictureFile->guessExtension();

                try {
                    $pictureFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                }

                if ($post->getPicture()) {
                    $oldPicturePath = $this->getParameter('uploads_directory') . '/' . $post->getPicture();
                    if (file_exists($oldPicturePath)) {
                        unlink($oldPicturePath);
                    }
                }

                $post->setPicture($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'L\'article a été mis à jour avec succès.');

            return $this->redirectToRoute('post_show', ['id' => $post->getId()]);
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/admin/posts/delete/{id}', name: 'post_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Post $post = null, EntityManagerInterface $entityManager): Response
    {
        if (!$post) {
            throw $this->createNotFoundException('L\'article demandé n\'existe pas.');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            if ($post->getPicture()) {
                $picturePath = $this->getParameter('uploads_directory') . '/' . $post->getPicture();
                if (file_exists($picturePath)) {
                    unlink($picturePath);
                }
            }

            $entityManager->remove($post);
            $entityManager->flush();

            $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Le jeton CSRF est invalide. L\'article n\'a pas été supprimé.');
        }

        return $this->redirectToRoute('app_home');
    }
}
