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
        $conn = $this->getEntityManager()->getConnection();

        $rangeSql = <<<'SQL'
            SELECT MIN(start_date) AS minStart, MAX(end_date) AS maxEnd
            FROM project
            WHERE start_date IS NOT NULL
              AND end_date IS NOT NULL
            SQL;
        $range = $conn->fetchAssociative($rangeSql);

        if (empty($range['minStart']) || empty($range['maxEnd'])) {
            return [];
        }

        $startYear = $startDate ? (int) $startDate->format('Y') : (int) (new \DateTime($range['minStart']))->format('Y');
        $endYear = $endDate ? (int) $endDate->format('Y') : (int) (new \DateTime($range['maxEnd']))->format('Y');

        $sql = <<<'SQL'
            SELECT start_date, end_date
            FROM project
            WHERE start_date IS NOT NULL
              AND end_date IS NOT NULL
              AND status = :status
              AND end_date BETWEEN :start AND :end
            SQL;

        $rows = $conn->fetchAllAssociative($sql, [
            'status' => 'completed',
            'start' => (new \DateTime())->setDate($startYear, 1, 1)->setTime(0, 0)->format('Y-m-d H:i:s'),
            'end' => (new \DateTime())->setDate($endYear, 12, 31)->setTime(23, 59)->format('Y-m-d H:i:s'),
        ]);

        $data = [];
        for ($y = $startYear; $y <= $endYear; $y++) {
            $data[(string) $y] = 0;
        }

        foreach ($rows as $row) {
            $from = max($startYear, (int) (new \DateTime($row['start_date']))->format('Y'));
            $to = min($endYear, (int) (new \DateTime($row['end_date']))->format('Y'));

            for ($y = $from; $y <= $to; $y++) {
                $data[(string) $y]++;
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
