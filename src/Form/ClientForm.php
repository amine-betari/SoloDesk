<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints as Assert;


class ClientForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('email', EmailType::class, [
                'label' => 'client.email',
                'required' => false, // ou true si tu veux le rendre obligatoire
                'constraints' => [
                    new Assert\Email([
                        'message' => 'Veuillez entrer une adresse email valide.',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'client.phone',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^\+?[0-9\s\-]{6,20}$/',
                        'message' => 'Veuillez entrer un numéro de téléphone valide',
                    ]),
                ],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'client.address',
            ])
            ->add('country', CountryType::class, [
                'label' => 'client.country',
                'placeholder' => 'client.country_placeholder',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'client.notes',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'w-full rounded-md border-gray-300 text-black d-none',
                    'placeholder' => 'client.notes_placeholder'
                ],
            ])
            ->add('firstContactAt', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'client.first_contact',
                'html5' => true, // active le datepicker natif
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'client.currency',
                'choices' => [
                    'client.currency_eur' => 'EUR',
                    'client.currency_mad' => 'MAD',
                    'client.currency_usd' => 'USD',
                ],
                'placeholder' => 'client.currency_placeholder',
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
