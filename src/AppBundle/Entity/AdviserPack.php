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
    private $prices_plan = [
        'S' =>  5 * 7000,
        'M' => 15 * 6500,
        'L' => 25 * 6000
    ];

    private $amounts_plan = [
        'S' =>  5,
        'M' => 15,
        'L' => 25
    ];

    /**
     * @var string
     */
    private $pack;

    /**
     * @var int
     */
    private $price;

    /**
     * @var int
     */
    private $amount;

    /**
     * Set pack
     *
     * @param string $pack
     *
     * @return Pack
     */
    public function setPack($pack)
    {
        $this->pack = $pack;

        $this->price = $this->prices_plan[$this->pack];
        $this->amount = $this->amounts_plan[$this->pack];

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

    /**
     * Get price
     *
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Get amount
     *
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

}
