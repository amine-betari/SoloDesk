<?php

declare(strict_types=1);

namespace App\Form\AutoComplete;

use App\Entity\Collaborator;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class CollaboratorAutocompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Collaborator::class,
            'placeholder' => 'collaborator.search_placeholder',
            'searchable_fields' => ['name', 'role'],
            'query_builder' => function (EntityRepository $er, $search = null, $options = []) {
                $qb = $er->createQueryBuilder('c')
                    ->orderBy('c.name', 'ASC');

                $companyId = $options['extra_options']['company_id'] ?? null;
                if ($companyId) {
                    $qb->andWhere('c.company = :company')
                        ->setParameter('company', $companyId);
                }

                return $qb;
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
