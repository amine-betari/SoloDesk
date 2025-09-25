<?php
// src/Controller/EmbedController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmbedController extends AbstractController
{
    #[Route('/embed/demo', name: 'embed_demo', methods: ['GET'])]
    public function demo(): Response
    {
        $response = $this->render('embed/demo.html.twig', [
            'message' => 'Hello depuis Symfony (8001) !'
        ]);

        // Autoriser l’affichage en iframe depuis l’autre projet (ex: 127.0.0.1:8000)
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' http://culture.mc.wip/");
        // Désactiver X-Frame-Options si présent
        $response->headers->remove('X-Frame-Options');

        return $response;
    }
}
