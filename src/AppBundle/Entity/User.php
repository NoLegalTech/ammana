<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Entity;

/**
 * User
 */
class User
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
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $companyName;

    /**
     * @var string
     */
    private $enabled;

    /**
     * @var string
     */
    private $cif;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string
     */
    private $contactPerson;

    /**
     * @var string
     */
    private $activationHash;

    /**
     * @var string
     */
    private $numberEmployees;

    /**
     * @var string
     */
    private $sector;

    /**
     * @var string
     */
    private $logo;

    /**
     * @var string
     */
    private $roles;

    /**
     * @var string
     */
    private $quadernoId;

    /**
     * @var int
     */
    private $credits;


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
     * @return User
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
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set companyName
     *
     * @param string $companyName
     *
     * @return User
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * Get companyName
     *
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * Set enabled
     *
     * @param string $enabled
     *
     * @return User
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return string
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set cif
     *
     * @param string $cif
     *
     * @return User
     */
    public function setCif($cif)
    {
        $this->cif = $cif;

        return $this;
    }

    /**
     * Get cif
     *
     * @return string
     */
    public function getCif()
    {
        return $this->cif;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return User
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set contactPerson
     *
     * @param string $contactPerson
     *
     * @return User
     */
    public function setContactPerson($contactPerson)
    {
        $this->contactPerson = $contactPerson;

        return $this;
    }

    /**
     * Get contactPerson
     *
     * @return string
     */
    public function getContactPerson()
    {
        return $this->contactPerson;
    }

    /**
     * Set activationHash
     *
     * @param string $activationHash
     *
     * @return User
     */
    public function setActivationHash($activationHash)
    {
        $this->activationHash = $activationHash;

        return $this;
    }

    /**
     * Get activationHash
     *
     * @return string
     */
    public function getActivationHash()
    {
        return $this->activationHash;
    }

    /**
     * Set numberEmployees
     *
     * @param string $numberEmployees
     *
     * @return User
     */
    public function setNumberEmployees($numberEmployees)
    {
        $this->numberEmployees = $numberEmployees;

        return $this;
    }

    /**
     * Get numberEmployees
     *
     * @return string
     */
    public function getNumberEmployees()
    {
        return $this->numberEmployees;
    }

    /**
     * Set sector
     *
     * @param string $sector
     *
     * @return User
     */
    public function setSector($sector)
    {
        $this->sector = $sector;

        return $this;
    }

    /**
     * Get sector
     *
     * @return string
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * Set logo
     *
     * @param string $logo
     *
     * @return User
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set roles
     *
     * @param string $roles
     *
     * @return User
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get roles
     *
     * @return string
     */
    public function getRoles()
    {
        return $this->roles;
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

    /**
     * Set credits
     *
     * @param int $credits
     *
     * @return User
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * Get credits
     *
     * @return int
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * String representation fo this object
     *
     * @return string
     */
    public function __toString() {
        return "User {\n"
                . "    id:            " . $this->getId()            . ",\n"
                . "    email:         " . $this->getEmail()         . ",\n"
                . "    companyName:   " . $this->getCompanyName()   . ",\n"
                . "    enabled:       " . $this->getEnabled()       . ",\n"
                . "    cif:           " . $this->getCif()           . ",\n"
                . "    address:       " . $this->getAddress()       . ",\n"
                . "    contactPerson: " . $this->getContactPerson() . ",\n"
                . "    roles:         " . $this->getRoles()         . ",\n"
                . "    quadernoId:    " . $this->getQuadernoId()    . ",\n"
                . "    credits:       " . $this->getCredits()       . "\n"
                . "}";
    }

}

