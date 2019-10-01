<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Entity;

/**
 * Pack
 */
class Pack
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var boolean
     */
    private $enabled;

    /**
     * @var int
     */
    private $user;

    /**
     * @var int
     */
    private $invoice;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var string
     */
    private $orderHash;

    /**
     * @var date
     */
    private $orderDate;

    /**
     * @var int
     */
    private $price;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return Pack
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set user
     *
     * @param int $user
     *
     * @return Pack
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return int
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set invoice
     *
     * @param int $invoice
     *
     * @return Pack
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * Get invoice
     *
     * @return int
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * Set amount
     *
     * @param int $amount
     *
     * @return Pack
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
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

    /**
     * Set orderHash
     *
     * @param string $orderHash
     *
     * @return Pack
     */
    public function setOrderHash($orderHash)
    {
        $this->orderHash = $orderHash;

        return $this;
    }

    /**
     * Get orderHash
     *
     * @return string
     */
    public function getOrderHash()
    {
        return $this->orderHash;
    }

    /**
     * Set orderDate
     *
     * @param date $orderDate
     *
     * @return Pack
     */
    public function setOrderDate($orderDate)
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    /**
     * Get orderDate
     *
     * @return date
     */
    public function getOrderDate()
    {
        return $this->orderDate;
    }

    /**
     * Set price
     *
     * @param int $price
     *
     * @return Pack
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
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
     * String representation fo this object
     *
     * @return string
     */
    public function __toString() {
        return "Pack {\n"
                . "    id: "         . $this->getId()                         . ",\n"
                . "    enabled: "    . $this->getEnabled()                    . ",\n"
                . "    user: "       . $this->getUser()                       . ",\n"
                . "    invoice: "    . $this->getInvoice()                    . ",\n"
                . "    orderHash: "  . $this->getOrderHash()                  . ",\n"
                . "    orderDate: "  . $this->getOrderDate()->format('d/m/Y') . ",\n"
                . "    price: "      . $this->getPrice()                      . "\n"
                . "}";
    }

}

