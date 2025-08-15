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

    public function findPaymentsForReports(): array
    {
        $qb = $this->createQueryBuilder('pay')
          //  ->leftJoin('pay.project', 'p')
            ->leftJoin('pay.salesDocument', 'sd')
           // ->leftJoin('p.client', 'pc')
           // ->leftJoin('sd.client', 'sdc')
           // ->addSelect('p', 'sd', 'pc', 'sdc')
            ->addSelect('sd')
            //->where('p.status = :projectStatus OR sd IS NOT NULL')
            ->Where('(sd IS NOT NULL AND sd.status IN (:allowedStatuses))') // paiements liés à des sales documents valides
            //->setParameter('projectStatus', ProjectStatuses::COMPLETED)
            ->setParameter('allowedStatuses', ['paid']) // à adapter selon ton InvoiceStatus
            ->orderBy('pay.date', 'ASC');


        return $qb->getQuery()->getResult();
    }


}
