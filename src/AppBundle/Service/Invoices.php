<?php

namespace AppBundle\Service;

use AppBundle\Service\Quaderno;
use AppBundle\Service\OrderNumberFormatter;

class Invoices {

    private $orderNumberFormatter;
    private $quaderno;

    public function __construct(OrderNumberFormatter $formatter, Quaderno $quaderno) {
        $this->orderNumberFormatter = $formatter;
        $this->quaderno = $quaderno;
    }

    public function getInvoicesForProtocols($protocols) {
        $invoices = array();
        foreach ($protocols as $protocol) {
            $orderNumber = $this->orderNumberFormatter->format($protocol->getId());
            $invoices[$protocol->getId()] = $this->quaderno->getInvoiceUrl($orderNumber);
        }
        return $invoices;
    }

}
