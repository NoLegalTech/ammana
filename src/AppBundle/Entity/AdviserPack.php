<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Entity;


/**
 * AdviserPack
 */
class AdviserPack
{
    /**
     * @var string
     */
    private $pack;

    /**
     * Set pack
     *
     * @param string $pack
     *
     * @return User
     */
    public function setPack($pack)
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack
     *
     * @return string
     */
    public function getPack()
    {
        return $this->pack;
    }

    public function getPrice()
    {
        if ($this->pack == 'S') {
            return 5 * 70;
        }
        if ($this->pack == 'S') {
            return 15 * 65;
        }
        if ($this->pack == 'S') {
            return 25 * 60;
        }
        return 999;
    }

}
