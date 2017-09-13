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

class AmpacheTvdb
{
    public $name           = 'Tvdb';
    public $categories     = 'metadata';
    public $description    = 'Tvdb metadata integration';
    public $url            = 'http://thetvdb.com';
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
        if (Preference::exists('tvdb_api_key')) {
            return false;
        }

        Preference::insert('tvdb_api_key', 'Tvdb api key', '', '75', 'string', 'plugins', $this->name);
        
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
        Preference::delete('tvdb_api_key');
        
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

        if (strlen(trim($data['tvdb_api_key']))) {
            $this->api_key = trim($data['tvdb_api_key']);
        } else {
            debug_event($this->name, 'No Tvdb api key, metadata plugin skipped', '3');

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
        debug_event('tvdb', 'Getting metadata from Tvdb...', '5');

        // TVShow metadata only
        if (!in_array('tvshow', $gather_types)) {
            debug_event('tvdb', 'Not a valid media type, skipped.', '5');

            return null;
        }
        
        try {
            $tvdburl = 'http://thetvdb.com';
            $client  = new Moinax\TvDb\Client($tvdburl, $this->api_key);
            $title   = $media_info['original_name'] ?: $media_info['title'];
            
            if ($media_info['tvshow']) {
                $releases = $client->getSeries($media_info['tvshow']);
                if (count($releases) == 0) {
                    throw new Exception("TV Show not found");
                }
                // Get first match
                $release                   = $this->getReleaseByTitle($releases, $media_info['tvshow'], $media_info['year']);
                $results['tvdb_tvshow_id'] = $release->id;
                $results['tvshow_imdb_id'] = $release->imdbId ;
                $results['tvshow_summary'] = substr($release->overview, 0, 255);   //Summary column in db is only 256 characters.
                $results['tvshow']         = $release->name;
                
                if ($release->FirstAired) {
                    $results['tvshow_year'] = $release->firstAired->format('Y');
                }
                if ($release->banner) {
                    $results['tvshow_banner_art'] = $tvdburl . '/banners/' . $release->banner;
                }
                $baseSeries = $client->getSerie($results['tvdb_tvshow_id']);
                    
                if (count($baseSeries->genres) > 0) {
                    $results['genre'] = $baseSeries->genres;
                }
                                        
                $banners = $client->getBanners($results['tvdb_tvshow_id']);
                foreach ($banners as $banner) {
                    if ($banner->language == "en") {
                        if (!$results['tvshow_art']) {
                            if ($banner->type == "poster") {
                                $results['tvshow_art'] = $tvdburl . '/banners/' . $banner->path;
                            }
                        }
                            
                        if ($media_info['tvshow_season'] && !$results['tvshow_season_art']) {
                            if ($banner->type == "season" && $banner->season == $media_info['tvshow_season']) {
                                $results['tvshow_season_art'] = $tvdburl . '/banners/' . $banner->path;
                            }
                        }
                    }
                }
                    
                if ($media_info['tvshow_season'] && $media_info['tvshow_episode']) {
                    $release = $client->getEpisode($results['tvdb_tvshow_id'], ltrim($media_info['tvshow_season'], "0"), ltrim($media_info['tvshow_episode'], "0"));
                    if ($release->id) {
                        $results['tvdb_id']        = $release->id;
                        $results['tvshow_season']  = $release->season;
                        $results['tvshow_episode'] = $release->number;
                        $results['original_name']  = $release->name;
                        $results['imdb_id']        = $release->imdbId ;
                        if ($release->firstAired) {
                            $results['release_date'] = $release->firstAired->getTimestamp();
                            $results['year']         = $release->firstAired->format('Y');
                        }
                        $results['summary'] = substr($release->overview, 0, 255);
                        if ($release->thumbnail) {
                            $results['art'] = $tvdburl . '/banners/' . $release->thumbnail;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            debug_event('tvdb', 'Error getting metadata: ' . $e->getMessage(), '1');
        }
        
        return $results;
    } // get_metadata

    public function gather_arts($type, $options = array(), $limit = 5)
    {
        debug_event('Tvdb', 'gather_arts for type `' . $type . '`', 5);

        return Art::gather_metadata_plugin($this, $type, $options);
    }
    
    private function getReleaseByTitle($results, $title, $year)
    {
        $titles = array();
        foreach ($results as $index) {
            $pos = strpos($index->name, $title);
            if ($pos !== false) {
                $titles[] = $index;
            }
        }
    
        if ((count($titles) > 1) && ($year != null)) {
            foreach ($titles as $index) {
                $y = $index->firstAired->format('Y');
                if ($year == $y) {
                    return $index;
                }
            }
        }

        return count($titles) > 0 ? $titles[0] : $results[0];
    }
} // end AmpacheTvdb
;
