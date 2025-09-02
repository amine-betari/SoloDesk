<?php

namespace App\Form\Search;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\SalesDocument;
use App\Form\AutoComplete\InvoiceAutocompleteField;
class PaymentFilterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('salesDocument', InvoiceAutocompleteField::class, [
                'class' => SalesDocument::class,
                'choice_label' => 'reference',
                'required' => false,
                'placeholder' => 'Tous les références',
            ])
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ]);
        $builder->add('search', SubmitType::class, ['label' => 'Filtrer']);
        $builder->add('reset', SubmitType::class, ['label' => 'Reset']);

    }
}
