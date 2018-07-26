<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

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
