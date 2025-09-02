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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use App\Form\AutoComplete\ClientAutocompleteField;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalesDocumentForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var SalesDocument|null $data */
        $data = $options['data'] ?? null;

        // Déterminer les statuts selon le type
        $statusChoices = [];
        if ($data instanceof SalesDocument) {
            if ($data->getType() === SalesDocument::TYPE_ESTIMATE) {
                $statusChoices = EstimateStatuses::CHOICES;
            } else {
                $statusChoices = InvoiceStatus::CHOICES;
            }
        }


        $builder
            ->add('reference')
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
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes / Informations complémentaires',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])

            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => $statusChoices
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SalesDocument::class,
        ]);
    }
}
