<?php

declare(strict_types=1);

namespace App\Form\Search;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\SalesDocument;
use App\Form\AutoComplete\ClientAutocompleteField;
use App\Form\AutoComplete\InvoiceAutocompleteField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PaymentFilterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('salesDocument', InvoiceAutocompleteField::class, [
                'class' => SalesDocument::class,
                'choice_label' => 'reference',
                'required' => false,
                'placeholder' => 'Tous les références',
            ])
            ->add('client', ClientAutocompleteField::class, [
                'required' => false,
                'placeholder' => 'Tous les clients',
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ]);
        $builder->add('search', SubmitType::class, ['label' => 'Filtrer']);
        $builder->add('reset', SubmitType::class, ['label' => 'Reset']);

    }
}
