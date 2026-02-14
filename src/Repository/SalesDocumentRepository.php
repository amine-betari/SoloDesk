<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\SalesDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SalesDocument>
 */
class SalesDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SalesDocument::class);
    }

    public function countEstimatesByStatus(Company $company, ?\DateTimeInterface $startDate = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.status AS status, COUNT(s.id) AS total')
            ->where('s.type = :type')
            ->andWhere('s.company = :company')
            ->setParameter('type', SalesDocument::TYPE_ESTIMATE)
            ->setParameter('company', $company);

        if ($startDate) {
            $qb->andWhere('COALESCE(s.invoiceDate, s.createdAt) >= :start')
               ->setParameter('start', $startDate);
        }

        return $qb->groupBy('s.status')
            ->getQuery()
            ->getArrayResult();
    }

    public function countInvoicesByYearAndExternal(Company $company, ?\DateTimeInterface $startDate = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = <<<'SQL'
            SELECT
                YEAR(invoice_date) AS year,
                external_invoice AS external_flag,
                COUNT(id) AS total
            FROM sales_document
            WHERE type = :type
              AND company_id = :companyId
              AND invoice_date IS NOT NULL
              AND (:startDate IS NULL OR invoice_date >= :startDate)
            GROUP BY year, external_flag
            ORDER BY year ASC
            SQL;

        return $conn->fetchAllAssociative($sql, [
            'type' => SalesDocument::TYPE_INVOICE,
            'companyId' => $company->getId(),
            'startDate' => $startDate ? $startDate->format('Y-m-d H:i:s') : null,
        ]);
    }

    //    /**
    //     * @return SalesDocument[] Returns an array of SalesDocument objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?SalesDocument
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
