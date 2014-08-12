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
 * Class TvSeason
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#tvseasons
 */
class TvSeason extends AbstractApi
{
    /**
     * Get the primary information about a TV season by its season number.
     *
     * @param $tvshow_id
     * @param $season_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getSeason($tvshow_id, $season_number, array $parameters = array(), array $headers = array())
    {
        return $this->get(sprintf('tv/%s/season/%s', $tvshow_id, $season_number), $parameters, $headers);
    }

    /**
     * Get the cast & crew credits for a TV season by season number.
     *
     * @param $tvshow_id
     * @param $season_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getCredits($tvshow_id, $season_number, array $parameters = array(), array $headers = array())
    {
        return $this->get(sprintf('tv/%s/season/%s/credits', $tvshow_id, $season_number), $parameters, $headers);
    }

    /**
     * Get the external ids that we have stored for a TV season by season number.
     *
     * @param $tvshow_id
     * @param $season_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getExternalIds($tvshow_id, $season_number, array $parameters = array(), array $headers = array())
    {
        return $this->get(sprintf('tv/%s/season/%s/external_ids', $tvshow_id, $season_number), $parameters, $headers);
    }

    /**
     * Get the images (posters) that we have stored for a TV season by season number.
     *
     * @param $tvshow_id
     * @param $season_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getImages($tvshow_id, $season_number, array $parameters = array(), array $headers = array())
    {
        return $this->get(sprintf('tv/%s/season/%s/images', $tvshow_id, $season_number), $parameters, $headers);
    }

    /**
     * Get the videos that have been added to a TV season (trailers, teasers, etc...)
     *
     * @param $tvshow_id
     * @param $season_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getVideos($tvshow_id, $season_number, array $parameters = array(), array $headers = array())
    {
        return $this->get(sprintf('tv/%s/season/%s/videos', $tvshow_id, $season_number), $parameters, $headers);
    }
}
