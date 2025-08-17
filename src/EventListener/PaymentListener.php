<?php

namespace App\EventListener;


use App\Entity\Payment;
use App\Repository\SalesDocumentRepository;
use App\Entity\SalesDocument;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;


class PaymentListener
{

    public function postPersist(Payment $payment, PostPersistEventArgs $args): void
    {
        $invoice = $payment->getSalesDocument();

        if ($invoice) {
            $invoice->setStatus('paid');
            $em = $args->getObjectManager();
            $em->persist($invoice);
            $em->flush();
        } else {
            return;
        }
    }

    public function postUpdate(Payment $payment, PostUpdateEventArgs $args): void
    {
        /* $invoice = $payment->getSalesDocument();
         if ($invoice) {
             $invoice->updateStatusBasedOnPayments();
             $args->getEntityManager()->persist($invoice);
             $args->getEntityManager()->flush();
         }*/

        $invoice = $payment->getSalesDocument();

        if ($invoice) {
            $invoice->setStatus('paid');
            $em = $args->getObjectManager();
            $em->persist($invoice);
            $em->flush();
        } else {
            return;
        }
    }
}
