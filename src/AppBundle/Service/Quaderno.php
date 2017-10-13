<?php

namespace AppBundle\Service;

use QuadernoBase;
use QuadernoInvoice;

class Quaderno {

    public function __construct($api_key, $api_url) {
        QuadernoBase::init($api_key, $api_url);
    }

    public function getInvoiceUrl($orderNumber) {
        $found = QuadernoInvoice::find(array('q' => $orderNumber));
        if (count($found) < 1) {
            return null;
        }
        return $found[0]->__get('pdf');
    }

}
