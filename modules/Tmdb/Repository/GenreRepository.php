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

use Tmdb\Factory\GenreFactory;
use Tmdb\Model\Common\GenericCollection;
use Tmdb\Model\Genre;

/**
 * Class GenreRepository
 * @package Tmdb\Repository
 * @see http://docs.themoviedb.apiary.io/#genres
 */
class GenreRepository extends AbstractRepository
{
    /**
     * Load a genre with the given identifier
     *
     * @param $id
     * @param  array $parameters
     * @param  array $headers
     * @return Genre
     */
    public function load($id, array $parameters = array(), array $headers = array())
    {
        return $this->loadCollection($parameters, $headers)->filterId($id);
    }

    /**
     * Get the list of genres.
     *
     * @param  array             $parameters
     * @param  array             $headers
     * @return GenericCollection
     */
    public function loadCollection(array $parameters = array(), array $headers = array())
    {
        return $this->createCollection(
            $this->getApi()->getGenres($parameters, $headers)
        );
    }

    /**
     * Get the list of movies for a particular genre by id.
     * By default, only movies with 10 or more votes are included.
     *
     * @param $id
     * @param  array   $parameters
     * @param  array   $headers
     * @return Genre[]
     */
    public function getMovies($id, array $parameters = array(), array $headers = array())
    {
        return $this->getFactory()->createResultCollection(
            $this->getApi()->getMovies($id, $parameters, $headers),
            'createMovie'
        );
    }

    /**
     * Create an collection of an array
     *
     * @param $data
     * @return GenericCollection|Genre[]
     */
    private function createCollection($data)
    {
        return $this->getFactory()->createCollection($data);
    }

    /**
     * Return the related API class
     *
     * @return \Tmdb\Api\Genres
     */
    public function getApi()
    {
        return $this->getClient()->getGenresApi();
    }

    /**
     * @return GenreFactory
     */
    public function getFactory()
    {
        return new GenreFactory();
    }
}
