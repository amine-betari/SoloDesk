<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function countClientsGroupedByYear(Company $company, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $companyId = $company->getId();

        if (!$startDate || !$endDate) {
            $rangeSql = <<<'SQL'
                SELECT MIN(first_contact_at) AS minDate, MAX(first_contact_at) AS maxDate
                FROM client
                WHERE first_contact_at IS NOT NULL
                  AND company_id = :companyId
                SQL;
            $range = $conn->fetchAssociative($rangeSql, [
                'companyId' => $companyId,
            ]);

            if (empty($range['minDate']) || empty($range['maxDate'])) {
                return [];
            }

            $startDate = $startDate ?: new \DateTime($range['minDate']);
            $endDate = $endDate ?: new \DateTime($range['maxDate']);
        }

        $startYear = (int) $startDate->format('Y');
        $endYear = (int) $endDate->format('Y');

        $sql = <<<'SQL'
            SELECT YEAR(first_contact_at) AS year, COUNT(id) AS total
            FROM client
            WHERE first_contact_at IS NOT NULL
              AND company_id = :companyId
              AND first_contact_at BETWEEN :start AND :end
            GROUP BY year
            ORDER BY year ASC
            SQL;

        $rows = $conn->fetchAllAssociative($sql, [
            'companyId' => $companyId,
            'start' => $startDate->setTime(0, 0)->format('Y-m-d H:i:s'),
            'end' => $endDate->setTime(23, 59)->format('Y-m-d H:i:s'),
        ]);

        $data = [];
        for ($y = $startYear; $y <= $endYear; $y++) {
            $data[(string) $y] = 0;
        }

        foreach ($rows as $row) {
            $year = (string) $row['year'];
            $data[$year] = (int) $row['total'];
        }

        ksort($data);
        return $data;
    }

}
