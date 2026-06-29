<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Expense;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ExpenseForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('spentAt', DateType::class, [
                'label' => 'expense.spent_at',
                'widget' => 'single_text',
            ])
            ->add('label', TextType::class, [
                'label' => 'expense.label',
                'constraints' => [
                    new NotBlank(message: 'Veuillez entrer un libellé'),
                ],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'expense.amount',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'step' => '0.01',
                ],
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'expense.currency',
                'choices' => [
                    'MAD' => 'MAD',
                    'EUR' => 'EUR',
                    'USD' => 'USD',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'expense.category',
                'choices' => [
                    'expense.category_hosting' => Expense::CATEGORY_HOSTING,
                    'expense.category_subscription' => Expense::CATEGORY_SUBSCRIPTION,
                    'expense.category_bank' => Expense::CATEGORY_BANK,
                    'expense.category_equipment' => Expense::CATEGORY_EQUIPMENT,
                    'expense.category_subcontracting' => Expense::CATEGORY_SUBCONTRACTING,
                    'expense.category_travel' => Expense::CATEGORY_TRAVEL,
                    'expense.category_other' => Expense::CATEGORY_OTHER,
                ],
            ])
            ->add('supplier', TextType::class, [
                'label' => 'expense.supplier',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'expense.notes',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Expense::class,
        ]);
    }
}
