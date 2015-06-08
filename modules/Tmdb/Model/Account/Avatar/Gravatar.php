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
namespace Tmdb\Model\Account\Avatar;

use Tmdb\Model\AbstractModel;

/**
 * Class Gravatar
 * @package Tmdb\Model\Account
 */
class Gravatar extends AbstractModel
{
    /**
     * @var string
     */
    private $hash;

    /**
     * @var array
     */
    public static $properties = [
        'hash'
    ];

    /**
     * @param  string $hash
     * @return $this
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }
}
