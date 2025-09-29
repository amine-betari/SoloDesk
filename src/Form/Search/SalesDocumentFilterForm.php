<?php

namespace App\Form\Search;

use App\Constants\EstimateStatuses;
use App\Constants\InvoiceStatus;
use App\Entity\SalesDocument;
use App\Form\AutoComplete\InvoiceAutocompleteField;
use App\Form\EstimateForm;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Client;
use App\Form\AutoComplete\ClientAutocompleteField;
use Symfony\Component\HttpFoundation\RequestStack;

class SalesDocumentFilterForm extends AbstractType
{
    public function __construct(
        protected RequestStack $requestStack,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws ORMException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $searchParams = $this->requestStack->getSession()->get('document_sales_filter', []);

        $statusChoicesInvoice  = InvoiceStatus::CHOICES;
        $statusChoicesEstimate = EstimateStatuses::CHOICES;

        $client = $searchParams['client'] ?? null;

        if ($client instanceof Client) {
            // Forcer Doctrine à travailler avec une entité managée
            $client = $this->entityManager->getReference(Client::class, $client->getId());
        }

        $builder
           /* ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Tous les clients',
            ])*/
            ->add('client', ClientAutocompleteField::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Tous les clients',
             //  'mapped' => false, // c'est un formulaire de recherche
            //  'data' => $searchParams['client'] ?? null,
                 'data' => $client,
            ])
            ->add('status', ChoiceType::class, [
               'label' => 'Status',
               'choices' => array_merge($statusChoicesEstimate,$statusChoicesInvoice),
               'required' => false,
               'placeholder' => 'Tous les status',
               'data' => $searchParams['status'] ?? null,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Devis' => 'estimate',
                    'Facture' => 'invoice',
                  //  'Facture - Projet' => 'project',
                ],
                'required' => false,
                'placeholder' => 'Tous les types',
                'data' => $searchParams['type'] ?? null,
            ])
        ;
        $builder->add('search', SubmitType::class, ['label' => 'Filtrer']);
        $builder->add('reset', SubmitType::class, ['label' => 'Reset']);

    }
}
