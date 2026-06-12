<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Constants\ProjectStatuses;
use App\Constants\ProjectTypes;
use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Estimate;
use App\Service\ProjectFromEstimateFactory;
use PHPUnit\Framework\TestCase;

final class ProjectFromEstimateFactoryTest extends TestCase
{
    public function testCreatePrefillsProjectWithoutLinkingOrPersistingIt(): void
    {
        $company = (new Company())->setName('ACME');
        $client = (new Client())->setName('Client')->setCompany($company);
        $estimate = (new Estimate())
            ->setEstimateNumber('EST-2026-ABC123')
            ->setClient($client)
            ->setDescription('Description')
            ->setAmount('1200.00')
            ->setVatRate('20.00');
        $estimate->setCurrency('EUR');

        $project = (new ProjectFromEstimateFactory())->create($estimate);

        self::assertNull($project->getId());
        self::assertNull($estimate->getProject());
        self::assertSame('EST-2026-ABC123', $project->getName());
        self::assertSame(ProjectStatuses::IN_PROGRESS, $project->getStatus());
        self::assertSame(ProjectTypes::AUTRE, $project->getType());
        self::assertSame($client, $project->getClient());
        self::assertSame($company, $project->getCompany());
        self::assertSame('Description', $project->getDescription());
        self::assertSame('1200.00', $project->getAmount());
        self::assertSame('20.00', $project->getVatRate());
        self::assertSame('EUR', $project->getCurrency());
    }
}
