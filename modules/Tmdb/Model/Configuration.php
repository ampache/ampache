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
 * Class Configuration
 * @package Tmdb\Model
 */
class Configuration extends AbstractModel
{
    /**
     * @var array
     */
    private $images;

    /**
     * @var array
     */
    private $change_keys;

    public static $properties = array(
        'images',
        'change_keys',
    );

    /**
     * @param  array $change_keys
     * @return $this
     */
    public function setChangeKeys(array $change_keys = array())
    {
        $this->change_keys = $change_keys;

        return $this;
    }

    /**
     * @return array
     */
    public function getChangeKeys()
    {
        return $this->change_keys;
    }

    /**
     * @param  array $images
     * @return $this
     */
    public function setImages(array $images = array())
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }
}
