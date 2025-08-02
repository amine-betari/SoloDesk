<?php

namespace App\Form;

use App\Entity\SalesDocument;
use App\Entity\SalesDocumentItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalesDocumentItemForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Prix Unitaire',
                'currency' => false,
                'scale' => 2,
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'scale' => 2,
            ])
            ->add('lineTotal', MoneyType::class, [
                'label' => 'Total Ligne',
                'currency' => false,
                'scale' => 2,
               // 'disabled' => true,
                'required' => false,
                // 'attr' => ['readonly' => true],
            ])
           /* ->add('salesDocument', EntityType::class, [
                'class' => SalesDocument::class,
                'choice_label' => 'id',
            ])*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SalesDocumentItem::class,
        ]);
    }
}
