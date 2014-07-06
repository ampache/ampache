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
 * Class Account
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#account
 */
class Account extends AbstractApi
{
    /**
     * Get the basic information for an account. You will need to have a valid session id.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getAccount(array $parameters = array(), array $headers = array())
    {
        return $this->get('account', $parameters, $headers);
    }

    /**
     * Get the lists that you have created and marked as a favorite.
     *
     * @param  integer $accountId
     * @param  array   $parameters
     * @param  array   $headers
     * @return mixed
     */
    public function getLists($accountId, array $parameters = array(), array $headers = array())
    {
        return $this->get('account/' . $accountId . '/lists', $parameters, $headers);
    }

    /**
     * Get the list of favorite movies for an account.
     *
     * @param  integer $accountId
     * @param  array   $parameters
     * @param  array   $headers
     * @return mixed
     */
    public function getFavoriteMovies($accountId, array $parameters = array(), array $headers = array())
    {
        return $this->get('account/' . $accountId . '/favorite_movies', $parameters, $headers);
    }

    /**
     * Add or remove a movie to an accounts favorite list.
     *
     * @param  integer $accountId
     * @param  integer $movieId
     * @param  boolean $isFavorite
     * @return mixed
     */
    public function favorite($accountId, $movieId, $isFavorite = true)
    {
        return $this->postJson('account/' . $accountId . '/favorite', array(
            'movie_id' => $movieId,
            'favorite' => $isFavorite
        ));
    }

    /**
     * Get the list of rated movies (and associated rating) for an account.
     *
     * @param  integer $accountId
     * @param  array   $parameters
     * @param  array   $headers
     * @return mixed
     */
    public function getRatedMovies($accountId, array $parameters = array(), array $headers = array())
    {
        return $this->get('account/' . $accountId . '/rated_movies', $parameters, $headers);
    }

    /**
     * Get the list of movies on an accounts watchlist.
     *
     * @param  integer $accountId
     * @param  array   $parameters
     * @param  array   $headers
     * @return mixed
     */
    public function getMovieWatchlist($accountId, array $parameters = array(), array $headers = array())
    {
        return $this->get('account/' . $accountId . '/movie_watchlist', $parameters, $headers);
    }

    /**
     * Add or remove a movie to an accounts watch list.
     *
     * @param  integer $accountId
     * @param  integer $movieId
     * @param  boolean $isOnWatchlist
     * @return mixed
     */
    public function watchlist($accountId, $movieId, $isOnWatchlist = true)
    {
        return $this->postJson('account/' . $accountId . '/movie_watchlist', array(
            'movie_id' => $movieId,
            'movie_watchlist' => $isOnWatchlist
        ));
    }
}
