<?php

namespace App\Service;

use App\Entity\Company;
use App\Entity\CompanySetting;
use App\Repository\CompanySettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class CompanySettings
{
    public const KEY_TAX_IMPOT_RATE = 'tax_impot_rate';
    public const KEY_ACTIVITY_START_DATE = 'activity_start_date';

    public function __construct(
        private CompanySettingRepository $repository,
        private EntityManagerInterface $em
    ) {}

    public function getFloat(Company $company, string $key, float $default): float
    {
        $setting = $this->repository->findOneByCompanyAndKey($company, $key);
        if (!$setting || $setting->getSettingValue() === null || $setting->getSettingValue() === '') {
            return $default;
        }

        return (float) $setting->getSettingValue();
    }

    public function getDate(Company $company, string $key, \DateTimeInterface $default): \DateTimeImmutable
    {
        $setting = $this->repository->findOneByCompanyAndKey($company, $key);
        if (!$setting || !$setting->getSettingValue()) {
            return \DateTimeImmutable::createFromInterface($default);
        }

        try {
            return new \DateTimeImmutable($setting->getSettingValue());
        } catch (\Exception) {
            return \DateTimeImmutable::createFromInterface($default);
        }
    }

    public function setFloat(Company $company, string $key, float $value): void
    {
        $this->upsert($company, $key, (string) $value, 'float');
    }

    public function setDate(Company $company, string $key, \DateTimeInterface $value): void
    {
        $this->upsert($company, $key, $value->format('Y-m-d'), 'date');
    }

    private function upsert(Company $company, string $key, string $value, string $type): void
    {
        $setting = $this->repository->findOneByCompanyAndKey($company, $key);
        if (!$setting) {
            $setting = new CompanySetting();
            $setting->setCompany($company);
            $setting->setSettingKey($key);
        }

        $setting->setSettingValue($value);
        $setting->setType($type);

        $this->em->persist($setting);
        $this->em->flush();
    }
}
