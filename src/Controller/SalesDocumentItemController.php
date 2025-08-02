<?php

namespace App\Controller;

use App\Entity\SalesDocumentItem;
use App\Form\SalesDocumentItemForm;
use App\Repository\SalesDocumentItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sales/document/item')]
final class SalesDocumentItemController extends AbstractController
{
    #[Route(name: 'app_sales_document_item_index', methods: ['GET'])]
    public function index(SalesDocumentItemRepository $salesDocumentItemRepository): Response
    {
        return $this->render('sales_document_item/index.html.twig', [
            'sales_document_items' => $salesDocumentItemRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_sales_document_item_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $salesDocumentItem = new SalesDocumentItem();
        $form = $this->createForm(SalesDocumentItemForm::class, $salesDocumentItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($salesDocumentItem);
            $entityManager->flush();

            return $this->redirectToRoute('app_sales_document_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sales_document_item/new.html.twig', [
            'sales_document_item' => $salesDocumentItem,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sales_document_item_show', methods: ['GET'])]
    public function show(SalesDocumentItem $salesDocumentItem): Response
    {
        return $this->render('sales_document_item/show.html.twig', [
            'sales_document_item' => $salesDocumentItem,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sales_document_item_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SalesDocumentItem $salesDocumentItem, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SalesDocumentItemForm::class, $salesDocumentItem);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_sales_document_item_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sales_document_item/edit.html.twig', [
            'sales_document_item' => $salesDocumentItem,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sales_document_item_delete', methods: ['POST'])]
    public function delete(Request $request, SalesDocumentItem $salesDocumentItem, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$salesDocumentItem->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($salesDocumentItem);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sales_document_item_index', [], Response::HTTP_SEE_OTHER);
    }
}
