<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use App\Entity\SalesDocument;
use Doctrine\ORM\EntityRepository;

#[AsEntityAutocompleteField]
class InvoiceAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => SalesDocument::class,
            'placeholder' => 'Veilluez choisir une facture ',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('s')
                    ->where('s.type = :type1')
                    ->orWhere('s.type = :type2')
                    ->setParameter('type1', 'invoice')
                    ->setParameter('type2', 'project')
                    ->orderBy('s.reference', 'ASC');
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
