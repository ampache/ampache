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
 * Class Companies
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#companies
 */
class Companies extends AbstractApi
{
    /**
     * This method is used to retrieve all of the basic information about a company.
     *
     * @param $company_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getCompany($company_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('company/' . $company_id, $parameters, $headers);
    }

    /**
     * Get the list of movies associated with a particular company.
     *
     * @param  integer $company_id
     * @param  array   $parameters
     * @param  array   $headers
     * @return mixed
     */
    public function getMovies($company_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('company/' . $company_id . '/movies', $parameters, $headers);
    }
}
