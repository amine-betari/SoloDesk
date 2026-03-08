<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\SalesDocument;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Payment;
use Symfony\Component\Validator\Constraints as Assert;
use App\Form\AutoComplete\InvoiceAutocompleteField;
class PaymentForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'payment.date'
            ])
            ->add('amount', /*MoneyType::class, [
                'currency' => 'EUR', // ou dynamique selon le projet
                'label' => 'Montant HT'
            ]*/)
            ->add('method', ChoiceType::class, [
                'choices' => [
                    'payment.method_card' => 'card',
                    'payment.method_transfer' => 'transfer',
                    'payment.method_check' => 'check',
                    'payment.method_cash' => 'cash',
                    'payment.method_paypal' => 'paypal',
                    'payment.method_other' => 'other',
                ],
                'placeholder' => 'payment.method_placeholder',
                'required' => false,
                'label' => 'payment.method'
            ])
            ->add('label', TextType::class, [
                'required' => false,
                'label' => 'payment.label'
            ])
            ->add('invoiceReference', TextType::class, [
            'required' => false,
            'label' => 'payment.invoice_reference_optional'
            ]);

        $builder
              ->add('salesDocument', InvoiceAutocompleteField::class, [
                  'required' => false,
              ])
          ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
            // Validation personnalisée au niveau du formulaire
            'constraints' => [
                new Assert\Callback([$this, 'validateProjectOrSalesDocument']),
            ],
        ]);
    }

    public function validateProjectOrSalesDocument(Payment $payment, $context)
    {
        if (!$payment->getSalesDocument()) {
            $context
                ->buildViolation('payment.validation_select_document')
                ->atPath('project') // ou 'salesDocument', juste pour pointer sur un champ
                ->addViolation();
        }
    }
}
