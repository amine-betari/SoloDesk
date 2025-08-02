<?php

namespace App\Repository;

use App\Entity\Client;
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

    public function countClientsGroupedByYear(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        // Déterminer la plage automatiquement si absente
        if (!$startDate || !$endDate) {
            $range = $this->createQueryBuilder('c')
                ->select('MIN(c.firstContactAt) AS minDate, MAX(c.firstContactAt) AS maxDate')
                ->where('c.firstContactAt IS NOT NULL')
                ->getQuery()
                ->getOneOrNullResult();

            if (!$range['minDate'] || !$range['maxDate']) {
                return []; // aucune donnée
            }

            $startDate = $startDate ?: new \DateTime($range['minDate']);
            $endDate = $endDate ?: new \DateTime($range['maxDate']);
        }

        $startYear = (int) $startDate->format('Y');
        $endYear = (int) $endDate->format('Y');

        $clients = $this->createQueryBuilder('c')
            ->where('c.firstContactAt IS NOT NULL')
            ->andWhere('c.firstContactAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate->setTime(0, 0))
            ->setParameter('end', $endDate->setTime(23, 59))
            ->getQuery()
            ->getResult();

        // Init tableau avec toutes les années même si 0
        $data = [];
        for ($y = $startYear; $y <= $endYear; $y++) {
            $data[(string)$y] = 0;
        }

        foreach ($clients as $client) {
            $year = $client->getFirstContactAt()->format('Y');
            $data[$year]++;
        }

        ksort($data);
        return $data;
    }

}
