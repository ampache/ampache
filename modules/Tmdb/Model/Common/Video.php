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
namespace Tmdb\Model\Common;

use Tmdb\Model\AbstractModel;

/**
 * Class Video
 * @package Tmdb\Model\Common
 */
class Video extends AbstractModel
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $iso6391;

    /**
     * @var mixed
     */
    private $key;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $site;

    /**
     * @var int
     */
    private $size;

    /**
     * @var string
     */
    private $type;

    /**
     * Holds the format of the url
     *
     * @var string
     */
    private $url_format;

    public static $properties = array(
        'id',
        'iso_639_1',
        'key',
        'name',
        'site',
        'size',
        'type'
    );

    /**
     * @param  string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
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
     * @param  mixed $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
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
     * @param  string $site
     * @return $this
     */
    public function setSite($site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * @return string
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param  int   $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param  string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  string $url_format
     * @return $this
     */
    public function setUrlFormat($url_format)
    {
        $this->url_format = $url_format;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrlFormat()
    {
        return $this->url_format;
    }

    /**
     * Retrieve the url to the source
     *
     * @return string
     */
    public function getUrl()
    {
        return sprintf($this->getUrlFormat(), $this->getKey());
    }
}
