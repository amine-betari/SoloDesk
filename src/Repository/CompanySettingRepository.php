<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\CompanySetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanySetting>
 */
class CompanySettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanySetting::class);
    }

    public function findOneByCompanyAndKey(Company $company, string $key): ?CompanySetting
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->andWhere('s.settingKey = :key')
            ->setParameter('company', $company)
            ->setParameter('key', $key)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
