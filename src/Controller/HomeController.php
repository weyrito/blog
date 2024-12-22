<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class HomeController extends AbstractController
{
    
    #[Route('/home', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Récupération des posts triés par date de publication décroissante
        $posts = $entityManager->getRepository(Post::class)->findBy([], ['publishedAt' => 'DESC']);

        if (!$posts) {
            $this->addFlash('info', 'Aucun article trouvé pour le moment.');
        }

        return $this->render('home/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('history', name: 'app_history')]
    public function history(): Response
    {
        return $this->render('home/history.html.twig');
    }

    #[Route('legal', name: 'app_legal')]
    public function legal(): Response
    {
        return $this->render('home/legal.html.twig');
    }

    #[Route('confidentialite', name: 'app_confidentiality')]
    public function confidentiality(): Response
    {
        return $this->render('home/confidentiality.html.twig');
    }
}
