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
            'label' => 'Référence facultative si pas de facture liée'
            ]);

        $builder
             /* ->add('project', ProjectAutocompleteField::class, [
                  'required' => false,
              ])*/
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
                ->buildViolation('Vous devez sélectionner soit un projet soit une facture.')
                ->atPath('project') // ou 'salesDocument', juste pour pointer sur un champ
                ->addViolation();
        }
    }
}
