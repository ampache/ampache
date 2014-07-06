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
 * Class Configuration
 * @package Tmdb\Api
 *
 * @see http://docs.themoviedb.apiary.io/#configuration
 */
class Configuration extends AbstractApi
{
    /**
     * Get the system wide configuration information.
     *
     * Some elements of the API require some knowledge of this configuration data.
     *
     * The purpose of this is to try and keep the actual API responses as light as possible.
     * It is recommended you store this data within your application and check for updates every so often.
     *
     * This method currently holds the data relevant to building image URLs as well as the change key map.
     *
     * To build an image URL, you will need 3 pieces of data.
     * The base_url, size and file_path.
     *
     * Simply combine them all and you will have a fully qualified URL. Here’s an example URL:
     *
     * http://d3gtl9l2a4fn1j.cloudfront.net/t/p/w500/8uO0gUM8aNqYLs1OsTBQiXu0fEv.jpg
     *
     * @param  array $headers
     * @return mixed
     */
    public function getConfiguration(array $headers = array())
    {
        return $this->get('configuration', array(), $headers);
    }
}
