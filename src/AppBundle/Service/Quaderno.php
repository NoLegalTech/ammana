<?php

namespace AppBundle\Service;

use QuadernoBase;
use QuadernoInvoice;

use AppBundle\Entity\Invoice;

class Quaderno {

    public function __construct($api_key, $api_url) {
        QuadernoBase::init($api_key, $api_url);
    }

    public function getInvoice($orderNumber) {
        $found = QuadernoInvoice::find(array('q' => $orderNumber));
        if (count($found) < 1) {
            return null;
        }
        $invoice = new Invoice();
        $invoice->setUser(null);
        $invoice->setEmittedAt(new \DateTime($found[0]->__get('issue_date')));
        $invoice->setNumber($found[0]->__get('number'));
        $invoice->setUrl($found[0]->__get('pdf'));
        return $invoice;
    }

}
