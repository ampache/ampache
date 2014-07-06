<?php
/**
 * This file is part of the Tmdb PHP API created by Michael Roterman.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Tmdb
 * @author Michael Roterman <michael@wtfz.net>
 * @copyright (c) 2013, Michael Roterman
 * @version 0.0.1
 */
namespace Tmdb;

/**
 * Class SessionToken
 * @package Tmdb
 */
class SessionToken
{
    /**
     * @var string
     */
    private $sessionToken = null;

    /**
     * @var \DateTime
     */
    private $expiresAt;

    /**
     * @var bool
     */
    private $success;

    /**
     * Token bag
     *
     * @param $session_token
     */
    public function __construct($session_token = null)
    {
        $this->sessionToken = $session_token;
    }

    /**
     * @param  null  $sessionToken
     * @return $this
     */
    public function setToken($sessionToken)
    {
        $this->sessionToken = $sessionToken;

        return $this;
    }

    /**
     * @return null
     */
    public function getToken()
    {
        return $this->sessionToken;
    }

    /**
     * @param  \DateTime $expiresAt
     * @return $this
     */
    public function setExpiresAt($expiresAt)
    {
        if (!$expiresAt instanceof \DateTime) {
            $expiresAt = new \DateTime($expiresAt);
        }

        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param  mixed $success
     * @return $this
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSuccess()
    {
        return $this->success;
    }
}
