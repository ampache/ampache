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

use Tmdb\Factory\AccountFactory;
use Tmdb\Model\Collection\ResultCollection;
use Tmdb\Model\Movie;

/**
 * Class AccountRepository
 * @package Tmdb\Repository
 * http://docs.themoviedb.apiary.io/#account
 */
class AccountRepository extends AbstractRepository
{
    /**
     * Get the basic information for an account.
     * You will need to have a valid session id.
     *
     * @return null|\Tmdb\Model\AbstractModel
     */
    public function getAccount()
    {
        $data  = $this->getApi()->getAccount();

        return $this->getFactory()->create($data);
    }

    /**
     * Get the lists that you have created and marked as a favorite.
     *
     * @param  string           $accountId
     * @param  array            $parameters
     * @param  array            $headers
     * @return ResultCollection
     */
    public function getLists($accountId, array $parameters = array(), array $headers = array())
    {
        $data  = $this->getApi()->getLists($accountId, $parameters, $headers);

        return $this->getFactory()->createResultCollection($data, 'createListItem');
    }

    /**
     * Get the list of favorite movies for an account.
     *
     * @param  string           $accountId
     * @param  array            $parameters
     * @param  array            $headers
     * @return ResultCollection
     */
    public function getFavoriteMovies($accountId, array $parameters = array(), array $headers = array())
    {
        $data  = $this->getApi()->getFavoriteMovies($accountId, $parameters, $headers);

        return $this->getFactory()->createResultCollection($data, 'createMovie');
    }

    /**
     * Add or remove a movie to an accounts favorite list.
     *
     * @param  string           $accountId
     * @param  int|Movie        $movie
     * @param  boolean          $isFavorite
     * @return ResultCollection
     */
    public function favorite($accountId, $movie, $isFavorite = true)
    {
        if ($movie instanceof Movie) {
            $movie = $movie->getId();
        }

        $data  = $this->getApi()->favorite($accountId, $movie, $isFavorite);

        return $this->getFactory()->createStatusResult($data);
    }

    /**
     * Get the list of rated movies (and associated rating) for an account.
     *
     * @param  string           $accountId
     * @param  array            $parameters
     * @param  array            $headers
     * @return ResultCollection
     */
    public function getRatedMovies($accountId, array $parameters = array(), array $headers = array())
    {
        $data  = $this->getApi()->getRatedMovies($accountId, $parameters, $headers);

        return $this->getFactory()->createResultCollection($data, 'createMovie');
    }

    /**
     * Get the list of movies on an accounts watchlist.
     *
     * @param  string           $accountId
     * @param  array            $parameters
     * @param  array            $headers
     * @return ResultCollection
     */
    public function getMovieWatchlist($accountId, array $parameters = array(), array $headers = array())
    {
        $data  = $this->getApi()->getMovieWatchlist($accountId, $parameters, $headers);

        return $this->getFactory()->createResultCollection($data, 'createMovie');
    }

    /**
     * Add or remove a movie to an accounts watch list.
     *
     * @param  string           $accountId
     * @param  int|Movie        $movie
     * @param  boolean          $isOnWatchlist
     * @return ResultCollection
     */
    public function watchlist($accountId, $movie, $isOnWatchlist = true)
    {
        if ($movie instanceof Movie) {
            $movie = $movie->getId();
        }

        $data  = $this->getApi()->watchlist($accountId, $movie, $isOnWatchlist);

        return $this->getFactory()->createStatusResult($data);
    }

    /**
     * Return the Collection API Class
     *
     * @return \Tmdb\Api\Account
     */
    public function getApi()
    {
        return $this->getClient()->getAccountApi();
    }

    /**
     * @return AccountFactory
     */
    public function getFactory()
    {
        return new AccountFactory();
    }
}
