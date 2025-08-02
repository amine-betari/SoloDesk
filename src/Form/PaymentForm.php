<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Payment;

class PaymentForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de paiement'
            ])
            ->add('amount', /*MoneyType::class, [
                'currency' => 'EUR', // ou dynamique selon le projet
                'label' => 'Montant HT'
            ]*/)
            ->add('method', ChoiceType::class, [
                'choices' => [
                    'Carte bancaire' => 'card',
                    'Virement bancaire' => 'transfer',
                    'Chèque' => 'check',
                    'Espèces' => 'cash',
                    'Paypal' => 'paypal',
                    'Autre' => 'other',
                ],
                'placeholder' => 'Choisir une méthode',
                'required' => false,
                'label' => 'Méthode de paiement'
            ])
            ->add('label', TextType::class, [
                'required' => false,
                'label' => 'Libellé'
            ])
            ->add('invoiceReference', TextType::class, [
            'required' => false,
            'label' => 'Libellé'
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
