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
 * Class Discover
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#discover
 */
class Discover extends AbstractApi
{
    /**
     * Discover movies by different types of data like average rating, number of votes, genres and certifications.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function discoverMovies(array $parameters = array(), array $headers = array())
    {
        return $this->get('discover/movie', $parameters, $headers);
    }

    /**
     * Discover TV shows by different types of data like average rating, number of votes, genres,
     * the network they aired on and air dates.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function discoverTv(array $parameters = array(), array $headers = array())
    {
        return $this->get('discover/tv', $parameters, $headers);
    }
}
