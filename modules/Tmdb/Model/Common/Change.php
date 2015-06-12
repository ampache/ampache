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
 * Class Change
 * @package Tmdb\Model\Common
 */
class Change extends AbstractModel
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var GenericCollection
     */
    private $items;

    public static $properties = array(
        'key',
    );

    public function __construct()
    {
        $this->items = new GenericCollection();
    }

    /**
     * @param  \Tmdb\Model\Common\GenericCollection $items
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @return \Tmdb\Model\Common\GenericCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param  string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }
}
