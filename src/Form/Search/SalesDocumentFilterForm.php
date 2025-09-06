<?php

namespace App\Form\Search;

use App\Constants\InvoiceStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Client;
use App\Form\AutoComplete\ClientAutocompleteField;

class SalesDocumentFilterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $statusChoices = InvoiceStatus::CHOICES;
        $builder
           /* ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Tous les clients',
            ])*/
            ->add('status', ChoiceType::class, [
               'label' => 'Status',
               'choices' => $statusChoices,
                'required' => false,
                'placeholder' => 'Tous les status',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Devis' => 'estimate',
                    'Facture' => 'invoice',
                    'Facture depuis un projet' => 'project',
                ],
                'required' => false,
                'placeholder' => 'Tous les types',
            ])
          /*  ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])*/
        ;
        $builder->add('search', SubmitType::class, ['label' => 'Filtrer']);
        $builder->add('reset', SubmitType::class, ['label' => 'Reset']);

    }
}
