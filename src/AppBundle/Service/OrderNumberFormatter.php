<?php

namespace AppBundle\Service;

class OrderNumberFormatter {

    private $prefix = "P";

    private $length = 8;

    public function format($number) {
        $formatted = "${number}";
        while (strlen($formatted) < $this->length) {
            $formatted = '0' . $formatted;
        }
        return $this->prefix . $formatted;
    }

}
