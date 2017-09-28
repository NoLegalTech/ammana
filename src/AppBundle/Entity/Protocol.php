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
     * @var date
     */
    private $expiresAt;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;


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
     * Set expiresAt
     *
     * @param date $expiresAt
     *
     * @return Protocol
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Get expiresAt
     *
     * @return date
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Protocol
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return Protocol
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

}

