<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Project;
use App\Entity\SalesDocument;
use App\Entity\Estimate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/search')]
final class SearchController extends AbstractController
{
    #[Route('', name: 'app_search', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $term = trim((string) $request->query->get('q', ''));

        $clients = [];
        $projects = [];
        $estimates = [];
        $salesDocuments = [];

        if ($term !== '' && mb_strlen($term) >= 2) {
            $like = '%' . $term . '%';

            $clients = $em->createQuery(
                'SELECT c FROM App\Entity\Client c
                 WHERE c.name LIKE :q OR c.email LIKE :q
                 ORDER BY c.name ASC'
            )
                ->setParameter('q', $like)
                ->setMaxResults(20)
                ->getResult();

            $projects = $em->createQuery(
                'SELECT p FROM App\Entity\Project p
                 WHERE p.name LIKE :q OR p.projectNumber LIKE :q
                 ORDER BY p.name ASC'
            )
                ->setParameter('q', $like)
                ->setMaxResults(20)
                ->getResult();

            $estimates = $em->createQuery(
                'SELECT e FROM App\Entity\Estimate e
                 WHERE e.name LIKE :q OR e.estimateNumber LIKE :q
                 ORDER BY e.startDate DESC'
            )
                ->setParameter('q', $like)
                ->setMaxResults(20)
                ->getResult();

            $salesDocuments = $em->createQuery(
                'SELECT s FROM App\Entity\SalesDocument s
                 WHERE s.reference LIKE :q
                 ORDER BY s.invoiceDate DESC'
            )
                ->setParameter('q', $like)
                ->setMaxResults(20)
                ->getResult();
        }

        return $this->render('search/index.html.twig', [
            'term' => $term,
            'clients' => $clients,
            'projects' => $projects,
            'estimates' => $estimates,
            'salesDocuments' => $salesDocuments,
        ]);
    }
}
