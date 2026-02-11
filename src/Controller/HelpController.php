<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HelpController extends AbstractController
{
    #[Route('/aide/estimation-devis-facture', name: 'app_help_estimation')]
    public function estimationDevisFacture(): Response
    {
        return $this->render('help/estimation_devis_facture.html.twig');
    }
}
