<?php

namespace App\Form\AutoComplete;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use App\Entity\SalesDocument;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityAutocompleteField]
class InvoiceAutocompleteField extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => SalesDocument::class,
            'placeholder' => 'Veilluez choisir une facture ',
            'searchable_fields' => ['reference', 'client.name'], // tu peux combiner reference + client.name
            'choice_label' => function (SalesDocument $document) {
                // Affiche "REF123 - Nom Client"
                $clientName = $document->getResolvedClient() ? $document->getResolvedClient()->getName() : '—';
                return sprintf('%s - %s', $document->getReference(), $clientName);
            },
            'query_builder' => function (EntityRepository $er) {
                $company = $this->security->getUser()?->getCompany();

                return $er->createQueryBuilder('s')
                    ->andWhere('s.company = :company')
                    ->andWhere('s.type = :type1 OR s.type = :type2')
                    ->setParameter('company', $company)
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
