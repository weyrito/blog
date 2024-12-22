<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Post;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    #[Route('/user', name: 'user_show')]
    public function show(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à votre profil.');
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/edit', name: 'user_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour modifier votre profil.');
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        $user->setUpdatedAt(new \DateTime());

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de la photo de profil
            $profilePictureFile = $form->get('profilePicture')->getData();
            if ($profilePictureFile) {
                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();

                try {
                    $profilePictureFile->move(
                        $this->getParameter('profile_pictures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                }

                // Suppression de l'ancienne image si nécessaire
                if ($user->getProfilePicture()) {
                    $oldProfilePicturePath = $this->getParameter('profile_pictures_directory') . '/' . $user->getProfilePicture();
                    if (file_exists($oldProfilePicturePath)) {
                        unlink($oldProfilePicturePath);
                    }
                }

                $user->setProfilePicture($newFilename);
            }

            // Persistance des modifications
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/{id}/posts', name: 'posts_by_user')]
    public function postsByUser(User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $posts = $entityManager->getRepository(Post::class)->findBy(['user' => $user]);

        if (!$posts) {
            $this->addFlash('info', 'Aucun article trouvé pour cet utilisateur.');
        }

        return $this->render('user/post.html.twig', [
            'posts' => $posts,
            'user' => $user,
        ]);
    }
}