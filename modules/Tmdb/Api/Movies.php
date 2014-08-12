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
 * Class Movies
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#movies
 */
class Movies extends AbstractApi
{
    /**
     * Get the basic movie information for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getMovie($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id, $parameters, $headers);
    }

    /**
     * Get the alternative titles for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getAlternativeTitles($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/alternative_titles', $parameters, $headers);
    }

    /**
     * Get the cast and crew information for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getCredits($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/credits', $parameters, $headers);
    }

    /**
     * Get the images (posters and backdrops) for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getImages($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/images', $parameters, $headers);
    }

    /**
     * Get the plot keywords for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getKeywords($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/keywords', $parameters, $headers);
    }

    /**
     * Get the release date by country for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getReleases($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/releases', $parameters, $headers);
    }

    /**
     * Get the trailers for a specific movie id.
     *
     * @deprecated TMDB changed the way of requesting trailers, see getVideos instead!
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getTrailers($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/trailers', $parameters, $headers);
    }

    /**
     * Get the translations for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getTranslations($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/translations', $parameters, $headers);
    }

    /**
     * Get the similar movies for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getSimilarMovies($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/similar_movies', $parameters, $headers);
    }

    /**
     * Get the reviews for a particular movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getReviews($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/reviews', $parameters, $headers);
    }

    /**
     * Get the lists that the movie belongs to.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getLists($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/lists', $parameters, $headers);
    }

    /**
     * Get the changes for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getChanges($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/changes', $parameters, $headers);
    }

    /**
     * Get the latest movie id.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getLatest(array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/latest', $parameters, $headers);
    }

    /**
     * Get the list of upcoming movies. This list refreshes every day.
     * The maximum number of items this list will include is 100.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getUpcoming(array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/upcoming', $parameters, $headers);
    }

    /**
     * Get the list of movies playing in theatres. This list refreshes every day.
     * The maximum number of items this list will include is 100.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getNowPlaying(array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/now_playing', $parameters, $headers);
    }

    /**
     * Get the list of popular movies on The Movie Database.
     * This list refreshes every day.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getPopular(array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/popular', $parameters, $headers);
    }

    /**
     * Get the list of top rated movies. By default, this list will only include
     * movies that have 10 or more votes. This list refreshes every day.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getTopRated(array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/top_rated', $parameters, $headers);
    }

    /**
     * This method lets users get the status of whether or not the movie has been rated
     * or added to their favourite or watch lists.
     *
     * A valid session id is required.
     *
     * @throws \Tmdb\Exception\NotImplementedException
     */
    public function getAccountStates($id)
    {
        return $this->get('movie/' . $id . '/account_states');
    }

    /**
     * TThis method lets users rate a movie.
     *
     * A valid session id or guest session id is required.
     *
     * @throws \Tmdb\Exception\NotImplementedException
     */
    public function rateMovie($id, $rating)
    {
        return $this->postJson('movie/' . $id . '/rating', array('value' => (float) $rating));
    }

    /**
     * Get the videos (trailers, teasers, clips, etc...) for a specific movie id.
     *
     * @param $movie_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getVideos($movie_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('movie/' . $movie_id . '/videos', $parameters, $headers);
    }
}
