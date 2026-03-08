<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Estimate;
use App\Entity\Project;
use App\Entity\SalesDocument;
use App\Constants\InvoiceStatus;
use App\Constants\EstimateStatuses;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Form\AutoComplete\ClientAutocompleteField;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class SalesDocumentForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var SalesDocument|null $data */
        $data = $options['data'] ?? null;

        // Déterminer les statuts selon le type
        $statusChoices = [];
        if ($data instanceof SalesDocument) {
            if ($data->isEstimate()) {
                $statusChoices = EstimateStatuses::CHOICES;
            } else {
                $statusChoices = InvoiceStatus::CHOICES;
            }
        }


        $builder
            ->add('reference', null, [
                'label' => 'sales_document.reference',
            ])
            // Champs TVA
            ->add('taxApplied', CheckboxType::class, [
                'label'    => 'sales_document.tax_applied',
                'required' => false,
            ])
            ->add('vatRate', NumberType::class, [
                'label'    => 'sales_document.vat_rate',
                'required' => false,
                'scale'    => 2,
            ])
            ->add('salesDocumentItems', CollectionType::class, [
                'entry_type' => SalesDocumentItemForm::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true, // important pour le JS
                'label' => false,
            ])
        ;
        // Champ client pour facture “directe”
           $builder->add('client', ClientAutocompleteField::class, [
                'required' => true,
                'choice_label' => 'name',
                'label' => 'sales_document.client',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'sales_document.notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'sales_document.status',
                'choices' => $statusChoices
            ])
               ->add('invoiceDate', DateType::class, [
                   'required' => false,
                   'widget' => 'single_text',
                   'label' => 'sales_document.issue_date',
                   'html5' => true, // active le datepicker natif
               ])
           ;

        if ($data instanceof SalesDocument && $data->isInvoice()) {
            $builder->add('externalInvoice', CheckboxType::class, [
                'label' => 'sales_document.external_invoice',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SalesDocument::class,
        ]);
    }
}
