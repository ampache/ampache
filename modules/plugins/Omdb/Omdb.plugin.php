<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

class AmpacheOmdb
{
    public $name           = 'Omdb';
    public $categories     = 'metadata';
    public $description    = 'OMDb metadata integration';
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
        $this->description = T_('OMDb metadata integration');

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
     * @param User $user
     * @return boolean
     */
    public function load($user)
    {
        $user->set_preferences();

        return true;
    } // load

    /**
     * @param string $title
     * @param string $year
     * @return mixed
     */
    protected function query_omdb($title, $year = '')
    {
        $url = 'http://www.omdbapi.com/?t=' . rawurlencode($title);
        if (!empty($year)) {
            $url .= '&y=' . rawurlencode($year);
        }
        $request = Requests::get($url, array(), Core::requests_options());

        return json_decode($request->body);
    }

    /**
     * @param string $runtime
     * @return float|int
     */
    protected function parse_runtime($runtime)
    {
        $time  = 0;
        $rtime = explode(' ', $runtime, 2);
        if (count($rtime) == 2) {
            if ($rtime[1] == 'min') {
                $time = (int) ($rtime[0]) * 60;
            }
        }

        return $time;
    }

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     * @param array $gather_types
     * @param array $media_info
     * @return array|null
     */
    public function get_metadata($gather_types, $media_info)
    {
        debug_event('omdb.plugin', 'Getting metadata from OMDb...', 5);

        // TVShow and Movie metadata only
        if (!in_array('tvshow', $gather_types) && !in_array('movie', $gather_types)) {
            debug_event('omdb.plugin', 'Not a valid media type, skipped.', 5);

            return null;
        }

        $title = $media_info['original_name'] ?: $media_info['title'];

        $results = array();
        try {
            // We cannot distinguish movies from tvshows with OMDb API (related to Imdb)
            $query = $this->query_omdb($title);
            if ($query->Response == 'True') {
                $match = false;
                $yse   = explode('-', $query->Year);
                if (in_array('movie', $gather_types) && $query->Type == 'movie') {
                    if ($yse[0] != "N/A") {
                        $results['year'] = $yse[0];
                    }
                    if ($query->Released != "N/A") {
                        $results['release_date'] = $query->Released;
                    }
                    $results['original_name'] = $query->Title;
                    $results['imdb_id']       = $query->imdbID;
                    if ($query->Plot != "N/A") {
                        $results['description'] = $query->Plot;
                    }
                    if ($query->Poster != "N/A") {
                        $results['art'] = $query->Poster;
                    }
                    $match = true;
                }

                if (in_array('tvshow', $gather_types) && $query->Type == 'series') {
                    if ($yse[0] != "N/A") {
                        $results['tvshow_year'] = $yse[0];
                    }
                    $results['tvshow'] = $query->Title;
                    if ($query->Plot != "N/A") {
                        $results['tvshow_description'] = $query->Plot;
                    }
                    $results['imdb_tvshow_id'] = $query->imdbID;
                    if ($query->Poster != "N/A") {
                        $results['tvshow_art'] = $query->Poster;
                    }
                    $match = true;
                }

                if ($match) {
                    if ($query->Runtime != "N/A") {
                        $results['time'] = $this->parse_runtime($query->Runtime);
                    }
                    $results['genre'] = $query->Genre;
                }
            }
        } catch (Exception $error) {
            debug_event('omdb.plugin', 'Error getting metadata: ' . $error->getMessage(), 1);
        }

        return $results;
    } // get_metadata

    /**
     * @param string $type
     * @param array $options
     * @param integer $limit
     * @return array
     */
    public function gather_arts($type, $options = array(), $limit = 5)
    {
        return Art::gather_metadata_plugin($this, $type, $options);
    }
} // end AmpacheOmdb
