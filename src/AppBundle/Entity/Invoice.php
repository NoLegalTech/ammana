<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Entity;

/**
 * Invoice
 */
class Invoice
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $user;

    /**
     * @var date
     */
    private $emittedAt;

    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $quadernoId;


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
     * Set user
     *
     * @param int $user
     *
     * @return Invoice
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
     * Set emittedAt
     *
     * @param date $emittedAt
     *
     * @return Invoice
     */
    public function setEmittedAt($emittedAt)
    {
        $this->emittedAt = $emittedAt;

        return $this;
    }

    /**
     * Get emittedAt
     *
     * @return date
     */
    public function getEmittedAt()
    {
        return $this->emittedAt;
    }

    /**
     * Set number
     *
     * @param string $number
     *
     * @return Invoice
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Invoice
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set quadernoId
     *
     * @param string $quadernoId
     *
     * @return User
     */
    public function setQuadernoId($quadernoId)
    {
        $this->quadernoId = $quadernoId;

        return $this;
    }

    /**
     * Get quadernoId
     *
     * @return string
     */
    public function getQuadernoId()
    {
        return $this->quadernoId;
    }

}

