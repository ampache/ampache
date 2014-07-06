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
use Tmdb\Exception\RuntimeException;

/**
 * Class ApiToken
 * @package Tmdb
 */
class ApiToken
{
    private $apiToken = null;

    /**
     * Token bag
     *
     * @param $api_token
     */
    public function __construct($api_token = null)
    {
        $this->apiToken = $api_token;
    }

    /**
     * @param  string           $apiToken
     * @throws RuntimeException
     * @return $this
     */
    public function setToken($apiToken)
    {
        if (!is_string($apiToken)) {
            throw new RuntimeException('The Apitoken must be set.');
        }

        $this->apiToken = $apiToken;

        return $this;
    }

    /**
     * @return null
     */
    public function getToken()
    {
        return $this->apiToken;
    }
}
