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
 * Class Certifications
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#certifications
 */
class Certifications extends AbstractApi
{
    /**
     * Get the list of supported certifications for movies.
     *
     * These can be used in conjunction with the certification_country and
     * certification.lte parameters when using discover.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getMovieList(array $parameters = array(), array $headers = array())
    {
        return $this->get('certification/movie/list', $parameters, $headers);
    }
}
