<?php

namespace App\Repository;

use App\Constants\ProjectStatuses;
use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findPaymentsForFinishedProjects(): array
    {
        return $this->createQueryBuilder('pay')
            ->join('pay.project', 'p')
            ->where('p.status = :status')
            ->setParameter('status', ProjectStatuses::COMPLETED)
            ->getQuery()
            ->getResult();
    }

}
