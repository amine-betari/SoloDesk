<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class ClientForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('email')
            ->add('country', CountryType::class, [
                'label' => 'Pays',
                'placeholder' => 'Choisissez un pays',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Informations complémentaires',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'w-full rounded-md border-gray-300 text-black d-none',
                    'placeholder' => 'Adresse, détails spécifiques, historique client...'
                ],
            ])
            ->add('firstContactAt', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date de premier contact',
                'html5' => true, // active le datepicker natif
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise',
                'choices' => [
                    'Euro (€)' => 'EUR',
                    'Dirham marocain (MAD)' => 'MAD',
                    'Dollar américain ($)' => 'USD',
                ],
                'placeholder' => 'Sélectionnez une devise',
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
