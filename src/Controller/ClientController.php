<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientForm;
use App\Repository\ClientRepository;
use App\Repository\PaginationService;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/client')]
final class ClientController extends AbstractController
{
    #[Route(name: 'app_client_index', methods: ['GET'])]
    public function index(
        ClientRepository $clientRepository,
        Request $request,
        PaginationService $paginator
    ): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $qb = $clientRepository->createQueryBuilder('c')
            ->andWhere('c.company = :company')
            ->setParameter('company', $company)
            ->orderBy('c.name', 'ASC');

        $pagination = $paginator->paginate($qb, $page, $limit);

        return $this->render('client/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $client = new Client();
        $company = $this->getUser()?->getCompany();
        if ($company) {
            $client->setCompany($company);
        }

        $form = $this->createForm(ClientForm::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($client);
            $entityManager->flush();

            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Client $client, ProjectRepository $projectRepo): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $client->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        $projectsCount = $projectRepo->count(['client' => $client]);

        return $this->render('client/show.html.twig', [
            'client' => $client,
            'projectsCount' => $projectsCount,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $client->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        $form = $this->createForm(ClientForm::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $client->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($client);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/create-from-modal', name: 'client_create_from_modal', methods: ['POST'])]
    public function createFromModal(Request $request, EntityManagerInterface $em, ClientRepository $clientRepository): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['error' => 'Requete invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $rawName = trim((string) ($data['name'] ?? ''));
        $currency = trim((string) ($data['currency'] ?? ''));

        if ($rawName === '') {
            return new JsonResponse(['error' => 'Le nom du client est obligatoire.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($currency === '') {
            return new JsonResponse(['error' => 'Veuillez choisir une devise.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $company = $this->getUser()?->getCompany();
        if (!$company) {
            return new JsonResponse(['error' => 'Entreprise introuvable.'], Response::HTTP_FORBIDDEN);
        }

        $name = preg_replace('/\\s+/', ' ', $rawName);
        $normalizedName = mb_strtolower($name);
        $existingClient = $clientRepository->findOneByNameForCompany($company, $normalizedName);
        if ($existingClient) {
            return new JsonResponse([
                'error' => 'Client deja existant.',
                'id' => $existingClient->getId(),
                'name' => $existingClient->getName(),
            ], Response::HTTP_CONFLICT);
        }

        $client = new Client();
        $client->setCompany($company);
        $client->setName($name);
        $client->setCurrency($currency);

        $em->persist($client);
        $em->flush();

        return $this->json([
            'id' => $client->getId(),
            'name' => $client->getName(),
        ]);
    }
}
