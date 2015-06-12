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
namespace Tmdb\Model\Common\Trailer;

use Tmdb\Model\Common\AbstractTrailer;

/**
 * Class Youtube
 * @package Tmdb\Model\Common\Trailer
 */
class Youtube extends AbstractTrailer
{
    const URL = 'http://www.youtube.com/watch?v=%s';

    private $name;
    private $size;
    private $source;
    private $type;

    public static $properties = array(
        'name',
        'size',
        'source',
        'type'
    );

    /**
     * Retrieve the url to the source
     *
     * @todo add bonus hd=1 query parameters, but we'd need some easy way of configuring this behaviour
     *
     * @return string
     */
    public function getUrl()
    {
        return sprintf(self::URL, $this->source);
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
     * @param  string $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return string
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param  string $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
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
}
