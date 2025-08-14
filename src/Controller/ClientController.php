<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientForm;
use App\Repository\ClientRepository;
use App\Repository\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        $qb = $clientRepository->createQueryBuilder('c')
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

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
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

    #[Route('/{id}', name: 'app_client_delete', methods: ['POST'])]
    public function delete(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($client);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/create-from-modal', name: 'client_create_from_modal', methods: ['POST', 'GET'])]
    public function createFromModal(Request $request, EntityManagerInterface $em): JsonResponse
    {
      //  $data = json_decode($request->getContent(), true);
      //  $data = json_decode($request->getContent(), true);
      //  $client = new Client();
      //  $client->setName($data['name']);
      //  $client->setCountry('FR');
      //  $em->persist($client);
      //  $em->flush();
        return 'OK';
        return $this->json([
            'id' => 11,
            'name' => 'AZERTY',
        ]);

        return $this->json([
            'id' => $client->getId(),
            'name' => $client->getName(),
        ]);
    }
}
