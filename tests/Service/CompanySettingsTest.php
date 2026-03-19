<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Company;
use App\Entity\CompanySetting;
use App\Repository\CompanySettingRepository;
use App\Service\CompanySettings;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CompanySettingsTest extends TestCase
{
    public function testGetIntFallsBackToDefaultWhenSettingMissing(): void
    {
        $repository = $this->createMock(CompanySettingRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneByCompanyAndKey')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $settings = new CompanySettings($repository, $entityManager);

        $company = new Company();

        self::assertSame(45, $settings->getInt($company, CompanySettings::KEY_OVERDUE_DAYS, 45));
    }

    public function testGetIntReturnsStoredValue(): void
    {
        $companySetting = new CompanySetting();
        $companySetting->setSettingValue('12');

        $repository = $this->createMock(CompanySettingRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneByCompanyAndKey')
            ->willReturn($companySetting);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $settings = new CompanySettings($repository, $entityManager);

        $company = new Company();

        self::assertSame(12, $settings->getInt($company, CompanySettings::KEY_OVERDUE_DAYS, 45));
    }
}
