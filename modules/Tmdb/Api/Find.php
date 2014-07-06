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
 * Class Find
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#find
 */
class Find extends AbstractApi
{
    /**
     * The find method makes it easy to search for objects in our database by an external id.
     *
     * For instance, an IMDB ID. This will search all objects (movies, TV shows and people)
     * and return the results in a single response.
     *
     * TV season and TV episode searches will be supported shortly.
     * The supported external sources for each object are as follows:
     *
     * Movies: imdb_id
     * People: imdb_id, freebase_mid, freebase_id, tvrage_id
     * TV Series: imdb_id, freebase_mid, freebase_id, tvdb_id, tvrage_id
     *
     * @param  string $id
     * @param  array  $parameters
     * @param  array  $headers
     * @return mixed
     */
    public function find($id, array $parameters = array(), array $headers = array())
    {
        return $this->get(
            sprintf('find/%s', $id),
            $parameters,
            $headers
        );
    }
}
