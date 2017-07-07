<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class AmpacheTmdb
{
    public $name           = 'Tmdb';
    public $categories     = 'metadata';
    public $description    = 'Tmdb metadata integration';
    public $url            = 'https://www.themoviedb.org';
    public $version        = '000003';
    public $min_ampache    = '370009';
    public $max_ampache    = '999999';
    
    // These are internal settings used by this class, run this->load to
    // fill them out
    private $api_key;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct()
    {
        return true;
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install()
    {
        if (Preference::exists('tmdb_api_key')) {
            return false;
        }

        Preference::insert('tmdb_api_key', 'Tmdb api key', '', '75', 'string', 'plugins', $this->name);
        
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
        Preference::delete('tmdb_api_key');
        
        return true;
    } // uninstall

    /**
     * load
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['tmdb_api_key']))) {
            $this->api_key = trim($data['tmdb_api_key']);
        } else {
            debug_event($this->name, 'No Tmdb api key, metadata plugin skipped', '3');

            return false;
        }
        
        return true;
    } // load

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata($gather_types, $media_info)
    {
        debug_event('tmdb', 'Getting metadata from Tmdb...', '5');

        // TVShow / Movie metadata only
        if (!in_array('tvshow', $gather_types) && !in_array('movie', $gather_types)) {
            debug_event('tmdb', 'Not a valid media type, skipped.', '5');

            return null;
        }
        
        try {
            $token            = new \Tmdb\ApiToken($this->api_key);
            $client           = new \Tmdb\Client($token);
            $configRepository = new \Tmdb\Repository\ConfigurationRepository($client);
            $config           = $configRepository->load();
            $imageHelper      = new \Tmdb\Helper\ImageHelper($config);
            
            $title = $media_info['original_name'] ?: $media_info['title'];
            
            $results = array();
            if (in_array('movie', $gather_types)) {
                if (!empty($media_info['title'])) {
                    $apires = $client->getSearchApi()->searchMovies($media_info['title']);
                    if (count($apires['results']) > 0) {
                        $results['tmdb_id']       = $apires['results'][0]['id'];
                        $release                  = $client->getMoviesApi()->getMovie($results['tmdb_id']);
                        $results['imdb_id']       = $release['imdb_id'];
                        $results['original_name'] = $release['original_title'];
                        if (!empty($release['release_date'])) {
                            $results['release_date'] = strtotime($release['release_date']);
                            $results['year']         = date("Y", $results['release_date']);  // Production year shouldn't be the release date
                        }
                        if ($release['poster_path']) {
                            $results['art'] = $imageHelper->getUrl($release['poster_path']);
                        }
                        if ($release['backdrop_path']) {
                            $results['background_art'] = $imageHelper->getUrl($release['backdrop_path']);
                        }
                        $results['genre']   = self::get_genres($release);
                        $results['summary'] = substr($release['overview'], 0, 255);
                    }
                }
            }
            
            if (in_array('tvshow', $gather_types)) {
                if (!empty($media_info['tvshow'])) {
                    $apires = $client->getSearchApi()->searchTv($media_info['tvshow']);
                    if (count($apires['results']) > 0) {
                        // Get first match
                        $results['tmdb_tvshow_id'] = $apires['results'][0]['id'];
                        $release                   = $client->getTvApi()->getTvshow($results['tmdb_tvshow_id']);
                        $results['tvshow']         = $release['original_name'];
                        if (!empty($release['first_air_date'])) {
                            $results['tvshow_year'] = date("Y", strtotime($release['first_air_date']));
                        }
                        if ($release['poster_path']) {
                            $results['tvshow_art'] = $imageHelper->getUrl($release['poster_path']);
                        }
                        if ($release['backdrop_path']) {
                            $results['tvshow_background_art'] = $imageHelper->getUrl($release['backdrop_path']);
                        }
                        $results['genre']          = self::get_genres($release);
                        $results['tvshow_summary'] = substr($release['overview'], 0, 255);
                        if ($media_info['tvshow_season']) {
                            $release = $client->getTvSeasonApi()->getSeason($results['tmdb_tvshow_id'], $media_info['tvshow_season']);
                            if ($release['id']) {
                                if ($release['poster_path']) {
                                    $results['tvshow_season_art'] = $imageHelper->getUrl($release['poster_path']);
                                }
                                if ($media_info['tvshow_episode']) {
                                    $release = $client->getTvEpisodeApi()->getEpisode($results['tmdb_tvshow_id'], $media_info['tvshow_season'], $media_info['tvshow_episode']);
                                    if ($release['id']) {
                                        $results['tmdb_id']        = $release['id'];
                                        $results['tvshow_season']  = $release['season_number'];
                                        $results['tvshow_episode'] = $release['episode_number'];
                                        $results['original_name']  = $release['name'];
                                        if (!empty($release['air_date'])) {
                                            $results['release_date'] = strtotime($release['air_date']);
                                            $results['year']         = date("Y", $results['release_date']);
                                        }
                                        $results['summary'] = substr($release['overview'], 0, 255);
                                        if ($release['still_path']) {
                                            $results['art'] = $imageHelper->getUrl($release['still_path']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            debug_event('tmdb', 'Error getting metadata: ' . $e->getMessage(), '1');
        }
        
        return $results;
    } // get_metadata

    public function gather_arts($type, $options = array(), $limit = 5)
    {
        debug_event('Tmdb', 'gather_arts for type `' . $type . '`', 5);

        return Art::gather_metadata_plugin($this, $type, $options);
    }
    
    private static function get_genres($release)
    {
        $genres = array();
        if (is_array($release['genres'])) {
            foreach ($release['genres'] as $genre) {
                if (!empty($genre['name'])) {
                    $genres[] = $genre['name'];
                }
            }
        }

        return $genres;
    }
} // end AmpacheTmdb
;
