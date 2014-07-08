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
 * Class Tv
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#tv
 */
class Tv extends AbstractApi
{
    /**
     * Get the primary information about a TV series by id.
     *
     * @param  integer $tvshow_id
     * @param  array   $parameters
     * @param  array   $headers
     * @return mixed
     */
    public function getTvshow($tvshow_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/' . $tvshow_id, $parameters, $headers);
    }

    /**
     * Get the cast & crew information about a TV series.
     * Just like the website, we pull this information from the last season of the series.
     *
     * @param $tvshow_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getCredits($tvshow_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/' . $tvshow_id . '/credits', $parameters, $headers);
    }

    /**
     * Get the external ids that we have stored for a TV series.
     *
     * @param $tvshow_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getExternalIds($tvshow_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/' . $tvshow_id . '/external_ids', $parameters, $headers);
    }

    /**
     * Get the images (posters and backdrops) for a TV series.
     *
     * @param $tvshow_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getImages($tvshow_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/' . $tvshow_id . '/images', $parameters, $headers);
    }

    /**
     * Get the list of popular TV shows. This list refreshes every day.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getPopular(array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/popular', $parameters, $headers);
    }

    /**
     * Get the list of top rated TV shows.
     *
     * By default, this list will only include TV shows that have 2 or more votes.
     * This list refreshes every day.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getTopRated(array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/top_rated', $parameters, $headers);
    }

    /**
     * Get the list of translations that exist for a TV series.
     *
     * These translations cascade down to the episode level.
     *
     * @param  int   $tvshow_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getTranslations($tvshow_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/' . $tvshow_id . '/translations', $parameters, $headers);
    }

    /**
     * Get the list of TV shows that are currently on the air.
     *
     * This query looks for any TV show that has an episode with an air date in the next 7 days.
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getOnTheAir(array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/on_the_air', $parameters, $headers);
    }

    /**
     * Get the list of TV shows that air today.
     *
     * Without a specified timezone, this query defaults to EST (Eastern Time UTC-05:00).
     *
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getAiringToday(array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/airing_today', $parameters, $headers);
    }

    /**
     * Get the videos that have been added to a TV series (trailers, opening credits, etc...)
     *
     * @param  int   $tvshow_id
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getVideos($tvshow_id, array $parameters = array(), array $headers = array())
    {
        return $this->get('tv/' . $tvshow_id . '/videos', $parameters, $headers);
    }
}
