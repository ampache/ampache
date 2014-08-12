<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class AmpacheTvdb {

    public $name           = 'Tvdb';
    public $description    = 'Tvdb metadata integration';
    public $version        = '000001';
    public $min_ampache    = '370009';
    public $max_ampache    = '999999';
    
    // These are internal settings used by this class, run this->load to
    // fill them out
    private $api_key;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct() {
        return true;
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install() {
        
        if (Preference::exists('tvdb_api_key')) { return false; }

        Preference::insert('tvdb_api_key','Tvdb api key','','75','string','plugins');
        
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall() {
    
        Preference::delete('tvdb_api_key');
        
        return true;
    } // uninstall

    /**
     * load
     * This is a required plugin function; here it populates the prefs we 
     * need for this object.
     */
    public function load($user) {
        
        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['tvdb_api_key']))) {
            $this->api_key = trim($data['tvdb_api_key']);
        }
        else {
            debug_event($this->name,'No Tvdb api key, metadata plugin skipped','3');
            return false;
        }
        
        return true;
    } // load

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata($gather_types, $media_info) {
        debug_event('tvdb', 'Getting metadata from Tvdb...', '5');

        // TVShow metadata only
        if (!in_array('tvshow', $gather_types)) {
            debug_event('tvdb', 'Not a valid media type, skipped.', '5');
            return null;
        }
        
        try {
            $tvdburl = 'http://thetvdb.com';
            $client = new Moinax\TvDb\Client($tvdburl, $this->api_key);
            $title = $media_info['original_name'] ?: $media_info['title'];
            
            $results = array();
            if (!empty($media_info['tvshow'])) {
                $releases = $client->getSeries($media_info['tvshow']);
                if (count($releases) > 0) {
                    // Get first match
                    $release = $releases[0];
                    $results['tvdb_tvshow_id'] = $release->id;
                    $results['tvshow_imdb_id'] = $release->imdbId ;
                    $results['summary'] = $release->overview;
                    $results['tvshow'] = $release->name;
                    if ($release->FirstAired) {
                        $results['tvshow_year'] = $release->firstAired->format('Y');
                    }
                    if ($release->banner) {
                        $results['tvshow_banner_art'] = $tvdburl . '/banners/' . $release->banner;
                    }
                    if (count($results->genres) > 0) {
                        $results['genre'] = $results->genres;
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
                        $release = $client->getEpisode($results['tvdb_tvshow_id'], $media_info['tvshow_season'], $media_info['tvshow_episode']);
                        if ($release->id) {
                            $results['tvdb_id'] = $release->id;
                            $results['tvshow_season'] = $release->season;
                            $results['tvshow_episode'] = $release->number;
                            $results['original_name'] = $release->name;
                            $results['imdb_id'] = $release->imdbId ;
                            if ($release->firstAired) {
                                $results['release_date'] = $release->firstAired->getTimestamp();
                                $results['year'] = $release->firstAired->format('Y');;
                            }
                            $results['description'] = $release->overview;
                            if ($release->thumbnail) {
                                $results['art'] = $tvdburl . '/banners/' . $release->thumbnail;
                            }
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

} // end AmpacheTvdb
?>
