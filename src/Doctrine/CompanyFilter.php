<?php

namespace App\Doctrine;

use App\Entity\Company;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class CompanyFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if ($targetEntity->getReflectionClass()->getName() === Company::class) {
            return '';
        }

        if (!$targetEntity->hasAssociation('company')) {
            return '';
        }

        $mapping = $targetEntity->getAssociationMapping('company');
        $joinColumn = $mapping['joinColumns'][0]['name'] ?? 'company_id';

        return sprintf('%s.%s = %s', $targetTableAlias, $joinColumn, $this->getParameter('company_id'));
    }
}
