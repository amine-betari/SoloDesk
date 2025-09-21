<?php

namespace App\EventListener;

use App\Entity\Estimate;
use App\Entity\Payment;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Doctrine\ORM\Event\PostFlushEventArgs;

class EstimateListener
{
    public function postUpdate(Estimate $estimate, PostUpdateEventArgs $args): void
    {
        $salesDocuments = $estimate->getSalesDocuments();

        if ($salesDocuments) {

            foreach ($estimate->getSalesDocuments() as $document) {
                $document->setStatus($estimate->getStatus());
            }
            //  $invoice->setStatus('paid');
            $em = $args->getObjectManager();
            $em->flush();
        }
    }
}
