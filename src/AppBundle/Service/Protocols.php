<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Service;

class Protocols {

    private $specs;

    public function __construct($ids, $specs) {
        $this->specs = [];
        for ($i = 0; $i < count($ids); $i++) {
            $this->specs[$ids[$i]] = $specs[$i];
        }
    }

    public function getName($id) {
        return $this->specs[$id]['name'];
    }

    public function getAll() {
        return $this->specs;
    }

}
