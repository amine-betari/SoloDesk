<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Estimate;
use App\Entity\Project;
use App\Entity\SalesDocument;
use PHPUnit\Framework\TestCase;

final class DeletionProtectionTest extends TestCase
{
    public function testEstimateCanOnlyBeDeletedWithoutProjectOrSalesDocument(): void
    {
        $estimate = new Estimate();

        self::assertTrue($estimate->canBeDeleted());

        $estimate->setProject(new Project());
        self::assertFalse($estimate->canBeDeleted());

        $estimate->setProject(null);
        $salesDocument = new SalesDocument();
        $estimate->addSalesDocument($salesDocument);
        self::assertFalse($estimate->canBeDeleted());

        $estimate->removeSalesDocument($salesDocument);
        self::assertTrue($estimate->canBeDeleted());
    }

    public function testProjectCanOnlyBeDeletedWithoutSalesDocument(): void
    {
        $project = new Project();

        self::assertTrue($project->canBeDeleted());

        $project->setEstimate(new Estimate());
        self::assertTrue($project->canBeDeleted());

        $salesDocument = new SalesDocument();
        $project->addSalesDocument($salesDocument);
        self::assertFalse($project->canBeDeleted());

        $project->removeSalesDocument($salesDocument);
        self::assertTrue($project->canBeDeleted());
    }
}
