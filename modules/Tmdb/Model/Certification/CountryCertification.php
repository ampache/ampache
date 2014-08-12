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
namespace Tmdb\Model\Certification;

use Tmdb\Model\AbstractModel;

/**
 * Class CountryCertification
 * @package Tmdb\Model\Certification
 */
class CountryCertification extends AbstractModel
{
    /**
     * @var string
     */
    private $certification;

    /**
     * @var string
     */
    private $meaning;

    /**
     * @var integer
     */
    private $order;

    public static $properties = array(
        'certification',
        'meaning',
        'order',
    );

    /**
     * @param  string $certification
     * @return $this
     */
    public function setCertification($certification)
    {
        $this->certification = $certification;

        return $this;
    }

    /**
     * @return string
     */
    public function getCertification()
    {
        return $this->certification;
    }

    /**
     * @param  string $meaning
     * @return $this
     */
    public function setMeaning($meaning)
    {
        $this->meaning = $meaning;

        return $this;
    }

    /**
     * @return string
     */
    public function getMeaning()
    {
        return $this->meaning;
    }

    /**
     * @param  int   $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }
}
