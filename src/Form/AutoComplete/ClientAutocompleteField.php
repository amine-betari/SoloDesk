<?php

namespace App\Form\AutoComplete;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use App\Entity\Client;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;

#[AsEntityAutocompleteField]
class ClientAutocompleteField extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Client::class,
            'placeholder' => 'Choisissez un client ',
            'query_builder' => function (EntityRepository $repository) {
                $company = $this->security->getUser()?->getCompany();

                return $repository->createQueryBuilder('client')
                    ->andWhere('client.company = :company')
                    ->setParameter('company', $company)
                    ->orderBy('client.name', 'ASC');
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
