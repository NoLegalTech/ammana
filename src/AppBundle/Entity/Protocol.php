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
    private $answers;

    /**
     * @var string
     */
    private $identifier;


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

}

