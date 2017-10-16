<?php

namespace AppBundle\Service;

use AppBundle\Service\Quaderno;
use AppBundle\Service\OrderNumberFormatter;
use AppBundle\Service\PermissionsService;
use AppBundle\Entity\Invoice;

class Invoices {

    protected $em;

    private $orderNumberFormatter;
    private $quaderno;
    private $permissions;

    public function __construct(\Doctrine\ORM\EntityManager $em, OrderNumberFormatter $formatter, Quaderno $quaderno, PermissionsService $permissions) {
        $this->em = $em;
        $this->orderNumberFormatter = $formatter;
        $this->quaderno = $quaderno;
        $this->permissions = $permissions;
    }

    public function getInvoicesForProtocols($protocols) {
        $invoices = array();
        foreach ($protocols as $protocol) {
            $invoice = $this->getInvoiceFromDb($protocol);
            if ($invoice == null) {
                $invoice = $this->getInvoiceFromQuaderno($protocol);
                $this->saveInvoiceToDb($protocol, $invoice);
            }
            $invoices[$protocol->getId()] = $invoice;
        }
        return $invoices;
    }

    private function getInvoiceFromDb($protocol) {
        $invoice_id = $protocol->getInvoice();
        if ($invoice_id == null) {
            return null;
        }
        $found = $this->em
            ->getRepository(Invoice::class)
            ->findById($invoice_id);

        if (!$found) {
            return null;
        }

        return $found[0];
    }

    private function getInvoiceFromQuaderno($protocol) {
        $orderNumber = $this->orderNumberFormatter->format($protocol->getId());
        $invoice = $this->quaderno->getInvoice($orderNumber);
        if ($invoice == null) {
            return null;
        }
        $invoice->setUser($this->permissions->getCurrentUser()->getId());
        return $invoice;
    }

    private function saveInvoiceToDb($protocol, $invoice) {
        if ($invoice == null) {
            return;
        }
        $this->em->persist($invoice);
        $this->em->flush();

        $protocol->setInvoice($invoice->getId());
        $this->em->flush();
    }

}
