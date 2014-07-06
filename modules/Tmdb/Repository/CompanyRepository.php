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
namespace Tmdb\Repository;

use Tmdb\Factory\CompanyFactory;
use Tmdb\Factory\MovieFactory;
use Tmdb\Model\Collection\ResultCollection;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Company;
use Tmdb\Model\Movie;

/**
 * Class CompanyRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#movies
 */
class CompanyRepository extends AbstractRepository
{
    /**
     * Load a company with the given identifier
     *
     * @param $id
     * @param  array   $parameters
     * @param  array   $headers
     * @return Company
     */
    public function load($id, array $parameters = array(), array $headers = array())
    {
        $data = $this->getApi()->getCompany($id, $this->parseQueryParameters($parameters), $headers);

        return $this->getFactory()->create($data);
    }

    /**
     * Get the list of movies associated with a particular company.
     *
     * @param  integer                   $id
     * @param  array                     $parameters
     * @param  array                     $headers
     * @return GenericCollection|Movie[]
     */
    public function getMovies($id, array $parameters = array(), array $headers = array())
    {
        return $this->createMovieCollection(
            $this->getApi()->getMovies($id, $this->parseQueryParameters($parameters), $headers)
        );
    }

    /**
     * Return the related API class
     *
     * @return \Tmdb\Api\Companies
     */
    public function getApi()
    {
        return $this->getClient()->getCompaniesApi();
    }

    /**
     * @return CompanyFactory
     */
    public function getFactory()
    {
        return new CompanyFactory();
    }

    /**
     * @return MovieFactory
     */
    public function getMovieFactory()
    {
        return new MovieFactory();
    }

    /**
     * Create an collection of an array
     *
     * @param $data
     * @return Movie[]
     */
    public function createMovieCollection($data)
    {
        $collection = new ResultCollection();

        if (array_key_exists('results', $data)) {
            $data = $data['results'];
        }

        foreach ($data as $item) {
            $collection->add(null, $this->getMovieFactory()->create($item));
        }

        return $collection;
    }
}
