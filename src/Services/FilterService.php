<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class FilterService
{
    public function handle(
        Request $request,
        QueryBuilder $qb,
        FormInterface $form,
        SessionInterface $session,
        string $sessionKey,
        string $resetRoute
    ): FormInterface
    {
        $form->handleRequest($request);

        // Reset du filtre si le bouton reset est cliqué
        if ($form->isSubmitted() && $form->get('reset')->isClicked()) {
            $session->remove($sessionKey);
            return $form; // le controller fera la redirection
        }

        $data = $form->getData() ?? [];

        // Stocker dans la session si soumis
        if ($form->isSubmitted() && $form->isValid()) {
            $session->set($sessionKey, $data);
        }

        // Charger les filtres depuis la session
        $searchParams = $session->get($sessionKey, []);

        // Appliquer les filtres dynamiquement
        foreach ($searchParams as $field => $value) {
            if ($value === null || $value === '') continue;

            $alias = $qb->getRootAliases()[0];
            switch ($field) {
                case 'client':
                    if ($alias === 's') { // ou selon l'alias passé depuis le controller
                        $qb->leftJoin("$alias.project", "p")
                            ->leftJoin("$alias.estimate", "e")
                            ->andWhere("s.client = :client OR p.client = :client OR e.client = :client")
                            ->setParameter('client', $value);
                    } else {
                        $qb->andWhere("$alias.client = :client")->setParameter('client', $value);
                    }
                   // $qb->andWhere("$alias.client = :client")->setParameter('client', $value);
                    break;
                case 'startDate':
                    $qb->andWhere("$alias.startDate >= :startDate")->setParameter('startDate', $value);
                    break;
                case 'endDate':
                    $qb->andWhere("$alias.startDate <= :endDate")->setParameter('endDate', $value);
                    break;
                case 'date':
                    $qb->andWhere("$alias.date >= :date")->setParameter('date', $value);
                    break;
                case 'salesDocument':
                    $qb->andWhere("$alias.salesDocument = :salesDocument")->setParameter('salesDocument', $value);
                    break;
                case 'type':
                    $qb->andWhere("$alias.type = :type")->setParameter('type', $value);
                    break;
                case 'status':
                    $qb->andWhere("$alias.status = :status")->setParameter('status', $value);
                    break;
                case 'externalInvoice':
                    $qb->andWhere("$alias.externalInvoice = :externalInvoice")
                        ->setParameter('externalInvoice', (int) $value);
                    break;
                // Ajouter d'autres champs dynamiquement si besoin
            }
        }

        return $form;
    }
}
