<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Entity;

/**
 * NewsletterSubscriber
 */
class NewsletterSubscriber
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $email;



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
     * Set email
     *
     * @param string $email
     *
     * @return NewsletterSubscriber
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * String representation fo this object
     *
     * @return string
     */
    public function __toString() {
        return "NewsletterSubscriber {\n"
                . "    id: "            . $this->getId()            . ",\n"
                . "    email: "         . $this->getEmail()         . "\n"
                . "}";
    }

}

