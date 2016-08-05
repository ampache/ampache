<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

class AmpacheOmdb
{
    public $name           = 'Omdb';
    public $categories     = 'metadata';
    public $description    = 'Omdb metadata integration';
    public $url            = 'http://www.omdbapi.com';
    public $version        = '000001';
    public $min_ampache    = '370009';
    public $max_ampache    = '999999';
    
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
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
        return true;
    } // uninstall

    /**
     * load
     * This is a required plugin function; here it populates the prefs we 
     * need for this object.
     */
    public function load($user)
    {
        return true;
    } // load

    protected function query_omdb($title, $year = '')
    {
        $url = 'http://www.omdbapi.com/?t=' . rawurlencode($title);
        if (!empty($year)) {
            $url .= '&y=' . rawurlencode($year);
        }
        $request = Requests::get($url, array(), Core::requests_options());
        return json_decode($request->body);
    }
    
    protected function parse_runtime($runtime)
    {
        $time = 0;
        $r    = explode(' ', $runtime, 2);
        if (count($r) == 2) {
            if ($r[1] == 'min') {
                $time = intval($r[0]) * 60;
            }
        }
        return $time;
    }

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata($gather_types, $media_info)
    {
        debug_event('omdb', 'Getting metadata from Omdb...', '5');

        // TVShow and Movie metadata only
        if (!in_array('tvshow', $gather_types) && !in_array('movie', $gather_types)) {
            debug_event('omdb', 'Not a valid media type, skipped.', '5');
            return null;
        }
        
        $title = $media_info['original_name'] ?: $media_info['title'];
        
        $results = array();
        try {
            // We cannot distinguish movies from tvshows with Omdb API (related to Imdb)
            $q = $this->query_omdb($title);
            if ($q->Response == 'True') {
                $match = false;
                $yse   = explode('-', $q->Year);
                if (in_array('movie', $gather_types) && $q->Type == 'movie') {
                    if ($yse[0] != "N/A") {
                        $results['year'] = $yse[0];
                    }
                    if ($q->Released != "N/A") {
                        $results['release_date'] = $q->Released;
                    }
                    $results['original_name'] = $q->Title;
                    $results['imdb_id']       = $q->imdbID;
                    if ($q->Plot != "N/A") {
                        $results['description'] = $q->Plot;
                    }
                    if ($q->Poster != "N/A") {
                        $results['art'] = $q->Poster;
                    }
                    $match = true;
                }
                
                if (in_array('tvshow', $gather_types) && $q->Type == 'series') {
                    if ($yse[0] != "N/A") {
                        $results['tvshow_year'] = $yse[0];
                    }
                    $results['tvshow'] = $q->Title;
                    if ($q->Plot != "N/A") {
                        $results['tvshow_description'] = $q->Plot;
                    }
                    $results['imdb_tvshow_id'] = $q->imdbID;
                    if ($q->Poster != "N/A") {
                        $results['tvshow_art'] = $q->Poster;
                    }
                    $match = true;
                }
                
                if ($match) {
                    if ($q->Runtime != "N/A") {
                        $results['time'] = $this->parse_runtime($q->Runtime);
                    }
                    $results['genre'] = $q->Genre;
                }
            }
        } catch (Exception $e) {
            debug_event('omdb', 'Error getting metadata: ' . $e->getMessage(), '1');
        }
        
        return $results;
    } // get_metadata

    public function gather_arts($type, $options = array(), $limit = 5)
    {
        return Art::gather_metadata_plugin($this, $type, $options);
    }
} // end AmpacheOmdb
;
