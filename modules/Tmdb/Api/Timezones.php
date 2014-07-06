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
namespace Tmdb\Api;

/**
 * Class Timezones
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#timezones
 */
class Timezones extends AbstractApi
{
    /**
     * Get the list of supported timezones for the API methods that support them.
     *
     * @return mixed
     */
    public function getTimezones()
    {
        return $this->get('timezones/list');
    }
}
