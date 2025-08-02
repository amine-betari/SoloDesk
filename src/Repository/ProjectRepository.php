<?php

namespace App\Repository;

use App\Constants\ProjectStatuses;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function countProjectsActiveByYear(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('MIN(p.startDate) AS minStart, MAX(p.endDate) AS maxEnd')
            ->where('p.startDate IS NOT NULL')
            ->andWhere('p.endDate IS NOT NULL');

        $range = $qb->getQuery()->getOneOrNullResult();

        if (!$range['minStart'] || !$range['maxEnd']) {
            return []; // Aucune donnée exploitable
        }

        $startYear = $startDate ? (int)$startDate->format('Y') : (int)(new \DateTime($range['minStart']))->format('Y');
        $endYear = $endDate ? (int)$endDate->format('Y') : (int)(new \DateTime($range['maxEnd']))->format('Y');

        $projects = $this->createQueryBuilder('p')
            ->where('p.startDate IS NOT NULL')
            ->andWhere('p.endDate IS NOT NULL')
            ->andWhere('p.status = :status')
            ->andWhere('p.endDate BETWEEN :start AND :end')
            ->setParameter('status', 'completed')
            ->setParameter('start', (new \DateTime())->setDate($startYear, 1, 1)->setTime(0, 0))
            ->setParameter('end', (new \DateTime())->setDate($endYear, 12, 31)->setTime(23, 59))
            ->getQuery()
            ->getResult();

        $data = [];
        for ($y = $startYear; $y <= $endYear; $y++) {
            $data[(string)$y] = 0;
        }

        foreach ($projects as $project) {
            $from = max($startYear, (int)$project->getStartDate()->format('Y'));
            $to = min($endYear, (int)$project->getEndDate()->format('Y'));

            for ($y = $from; $y <= $to; $y++) {
                $data[(string)$y]++;
            }
        }

        return $data;
    }


    public function findFinishedProjects(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status') // à adapter selon ta logique métier
            ->setParameter('status', ProjectStatuses::COMPLETED) // ou 'completed', etc.
            ->getQuery()
            ->getResult();
    }


}
