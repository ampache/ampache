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
namespace Tmdb\Model\Common\Change;

use Tmdb\Model\AbstractModel;

/**
 * Class Item
 * @package Tmdb\Model\Common\Change
 */
class Item extends AbstractModel
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $action;

    /**
     * @var \DateTime
     */
    private $time;

    /**
     * @var array
     */
    private $value;

    public static $properties = array(
        'id',
        'action',
        'time',
        'value'
    );

    /**
     * @param  string $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

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
     * @param  string|\DateTime $time
     * @return $this
     */
    public function setTime($time)
    {
        if (!$time instanceof \DateTime) {
            $time = new \DateTime($time);
        }

        $this->time = $time;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param  array $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getValue()
    {
        return $this->value;
    }
}
