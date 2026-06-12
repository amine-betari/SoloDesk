<?php

namespace App\Repository;

use App\Constants\InvoiceStatus;
use App\Constants\ProjectStatuses;
use App\Entity\Company;
use App\Entity\Payment;
use App\Entity\SalesDocument;
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

    public function findPaymentsForReports(Company $company): array
    {
        $qb = $this->createQueryBuilder('pay')
            ->leftJoin('pay.salesDocument', 'sd')
            ->addSelect('sd')
            ->where('sd IS NOT NULL')
            ->andWhere('sd.status = :paidStatus')
            ->andWhere('sd.company = :company')
            ->setParameter('paidStatus', 'paid')
            ->setParameter('company', $company)
            ->orderBy('pay.date', 'ASC');

        return $qb->getQuery()->getResult();

    }

    /**
     * @return Payment[]
     */
    public function findPaymentsForBillingComparison(
        Company $company,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        return $this->createQueryBuilder('pay')
            ->addSelect('sd', 'client', 'project', 'estimate')
            ->innerJoin('pay.salesDocument', 'sd')
            ->leftJoin('sd.client', 'client')
            ->leftJoin('sd.project', 'project')
            ->leftJoin('sd.estimate', 'estimate')
            ->andWhere('pay.company = :company')
            ->andWhere('sd.company = :company')
            ->andWhere('sd.type = :type')
            ->andWhere('sd.status != :cancelledStatus')
            ->andWhere('pay.date >= :startDate')
            ->andWhere('pay.date < :endDate')
            ->setParameter('company', $company)
            ->setParameter('type', SalesDocument::TYPE_INVOICE)
            ->setParameter('cancelledStatus', InvoiceStatus::CANCELLED)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('pay.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
