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
namespace Tmdb\Model;

/**
 * Class Account
 * @package Tmdb\Model
 */
class Account extends AbstractModel
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var boolean
     */
    private $includeAdult;

    /**
     * @var string
     */
    private $iso31661;

    /**
     * @var string
     */
    private $iso6391;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $username;

    /**
     * @var array
     */
    public static $properties = array(
        'id',
        'include_adult',
        'iso_3166_1',
        'iso_639_1',
        'name',
        'username'
    );

    /**
     * @param  int   $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  boolean $includeAdult
     * @return $this
     */
    public function setIncludeAdult($includeAdult)
    {
        $this->includeAdult = $includeAdult;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIncludeAdult()
    {
        return $this->includeAdult;
    }

    /**
     * @param  string $iso31661
     * @return $this
     */
    public function setIso31661($iso31661)
    {
        $this->iso31661 = $iso31661;

        return $this;
    }

    /**
     * @return string
     */
    public function getIso31661()
    {
        return $this->iso31661;
    }

    /**
     * @param  string $iso6391
     * @return $this
     */
    public function setIso6391($iso6391)
    {
        $this->iso6391 = $iso6391;

        return $this;
    }

    /**
     * @return string
     */
    public function getIso6391()
    {
        return $this->iso6391;
    }

    /**
     * @param  string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
}
