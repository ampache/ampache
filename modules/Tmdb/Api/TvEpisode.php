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
 * Class TvEpisode
 * @package Tmdb\Api
 * @see http://docs.themoviedb.apiary.io/#tvepisodes
 */
class TvEpisode extends AbstractApi
{
    /**
     * Get the primary information about a TV episode by combination of a season and episode number.
     *
     * @param $tvshow_id
     * @param $season_number
     * @param $episode_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getEpisode(
        $tvshow_id,
        $season_number,
        $episode_number,
        array $parameters = array(),
        array $headers = array()
    ) {
        return $this->get(
            sprintf(
                'tv/%s/season/%s/episode/%s',
                $tvshow_id,
                $season_number,
                $episode_number
            ),
            $parameters,
            $headers
        );
    }

    /**
     * Get the TV episode credits by combination of season and episode number.
     *
     * @param $tvshow_id
     * @param $season_number
     * @param $episode_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getCredits(
        $tvshow_id,
        $season_number,
        $episode_number,
        array $parameters = array(),
        array $headers = array()
    ) {
        return $this->get(
            sprintf(
                'tv/%s/season/%s/episode/%s/credits',
                $tvshow_id,
                $season_number,
                $episode_number
            ),
            $parameters,
            $headers
        );
    }

    /**
     * Get the external ids for a TV episode by comabination of a season and episode number.
     *
     * @param $tvshow_id
     * @param $season_number
     * @param $episode_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getExternalIds(
        $tvshow_id,
        $season_number,
        $episode_number,
        array $parameters = array(),
        array $headers = array()
    ) {
        return $this->get(
            sprintf(
                'tv/%s/season/%s/episode/%s/external_ids',
                $tvshow_id,
                $season_number,
                $episode_number
            ),
            $parameters,
            $headers
        );
    }

    /**
     * Get the images (episode stills) for a TV episode by combination of a season and episode number.
     *
     * @param $tvshow_id
     * @param $season_number
     * @param $episode_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getImages(
        $tvshow_id,
        $season_number,
        $episode_number,
        array $parameters = array(),
        array $headers = array()
    ) {
        return $this->get(
            sprintf(
                'tv/%s/season/%s/episode/%s/images',
                $tvshow_id,
                $season_number,
                $episode_number
            ),
            $parameters,
            $headers
        );
    }

    /**
     * Get the videos that have been added to a TV episode (teasers, clips, etc...)
     *
     * @param $tvshow_id
     * @param $season_number
     * @param $episode_number
     * @param  array $parameters
     * @param  array $headers
     * @return mixed
     */
    public function getVideos(
        $tvshow_id,
        $season_number,
        $episode_number,
        array $parameters = array(),
        array $headers = array()
    ) {
        return $this->get(
            sprintf(
                'tv/%s/season/%s/episode/%s/videos',
                $tvshow_id,
                $season_number,
                $episode_number
            ),
            $parameters,
            $headers
        );
    }
}
