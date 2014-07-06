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
 * Class AbstractModel
 * @package Tmdb\Model
 */
class AbstractModel
{
    /**
     * List of properties to populate by the ObjectHydrator
     *
     * @var array
     */
    public static $properties = array();
}
