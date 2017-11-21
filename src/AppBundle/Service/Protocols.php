<?php

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
