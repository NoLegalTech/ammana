<?php

namespace AppBundle\Entity;

/**
 * Protocol
 */
class Protocol
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
     * @var string
     */
    private $answers;

    /**
     * @var string
     */
    private $identifier;

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
     * @return Protocol
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
     * @return Protocol
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
     * @return Protocol
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
     * Set answers
     *
     * @param string $answers
     *
     * @return Protocol
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;

        return $this;
    }

    /**
     * Get answers
     *
     * @return string
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return Protocol
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set orderHash
     *
     * @param string $orderHash
     *
     * @return Protocol
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
     * @return Protocol
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
     * @return Protocol
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
        return "Protocol {\n"
                . "    id: "         . $this->getId()                         . ",\n"
                . "    enabled: "    . $this->getEnabled()                    . ",\n"
                . "    user: "       . $this->getUser()                       . ",\n"
                . "    invoice: "    . $this->getInvoice()                    . ",\n"
                . "    answers: "    . $this->getAnswers()                    . ",\n"
                . "    identifier: " . $this->getIdentifier()                 . ",\n"
                . "    orderHash: "  . $this->getOrderHash()                  . ",\n"
                . "    orderDate: "  . $this->getOrderDate()->format('d/m/Y') . ",\n"
                . "    price: "      . $this->getPrice()                      . "\n"
                . "}";
    }

}

