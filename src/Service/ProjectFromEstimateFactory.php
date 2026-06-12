<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\ProjectStatuses;
use App\Constants\ProjectTypes;
use App\Entity\Estimate;
use App\Entity\Project;

final class ProjectFromEstimateFactory
{
    public function create(Estimate $estimate): Project
    {
        $project = new Project();
        $project->setName((string) $estimate->getEstimateNumber());
        $project->setStartDate(new \DateTimeImmutable());
        $project->setStatus(ProjectStatuses::IN_PROGRESS);
        $project->setClient($estimate->getClient());
        $project->setCompany($estimate->getCompany());
        $project->setType(ProjectTypes::AUTRE);
        $project->setDescription($estimate->getDescription());
        $project->setAmount($estimate->getAmount());
        $project->setVatRate($estimate->getVatRate());
        $project->setCurrency($estimate->getCurrency());

        return $project;
    }
}
