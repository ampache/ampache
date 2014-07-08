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
 * Class RequestToken
 * @package Tmdb
 */
class RequestToken
{
    /**
     * The token for obtaining a session
     *
     * @var string
     */
    private $token = null;

    /**
     * Expiry date UTC
     *
     * @var
     */
    private $expiresAt;

    /**
     * @var bool
     */
    private $success;

    /**
     * Token bag
     *
     * @param $request_token
     */
    public function __construct($request_token = null)
    {
        $this->token = $request_token;
    }

    /**
     * @param  null  $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return null
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param  mixed $expiresAt
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
     * @return mixed
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param  boolean $success
     * @return $this
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSuccess()
    {
        return $this->success;
    }
}
