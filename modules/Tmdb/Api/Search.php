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
 * Class Search
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#search
 */
class Search extends AbstractApi
{
    /**
     * Search for movies by title.
     *
     * @param $query
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function searchMovies($query, array $parameters = array(), array $headers = array())
    {
        return $this->get('search/movie', array_merge($parameters, array(
            'query' => urlencode($query)
        ), $headers));
    }

    /**
     * Search for collections by name.
     *
     * @param $query
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function searchCollection($query, array $parameters = array(), array $headers = array())
    {
        return $this->get('search/collection', array_merge($parameters, array(
            'query' => urlencode($query)
        ), $headers));
    }

    /**
     * Search for TV shows by title.
     *
     * @param $query
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function searchTv($query, array $parameters = array(), array $headers = array())
    {
        return $this->get('search/tv', array_merge($parameters, array(
            'query' => urlencode($query)
        ), $headers));
    }

    /**
     * Search for people by name.
     *
     * @param $query
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function searchPersons($query, array $parameters = array(), array $headers = array())
    {
        return $this->get('search/person', array_merge($parameters, array(
            'query' => urlencode($query)
        ), $headers));
    }

    /**
     * Search for lists by name and description.
     *
     * @param $query
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function searchList($query, array $parameters = array(), array $headers = array())
    {
        return $this->get('search/list', array_merge($parameters, array(
            'query' => urlencode($query)
        ), $headers));
    }

    /**
     * Search for companies by name.
     *
     * @param $query
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function searchCompany($query, array $parameters = array(), array $headers = array())
    {
        return $this->get('search/company', array_merge($parameters, array(
            'query' => urlencode($query)
        ), $headers));
    }

    /**
     * Search for companies by name.
     *
     * @param $query
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function searchKeyword($query, array $parameters = array(), array $headers = array())
    {
        return $this->get('search/keyword', array_merge($parameters, array(
            'query' => urlencode($query)
        ), $headers));
    }
}
