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

use MusicBrainz\MusicBrainz;
use MusicBrainz\HttpAdapters\RequestsHttpAdapter;

class AmpacheMusicBrainz
{
    public $name           = 'MusicBrainz';
    public $categories     = 'metadata';
    public $description    = 'MusicBrainz metadata integration';
    public $url            = 'http://www.musicbrainz.org';
    public $version        = '000001';
    public $min_ampache    = '360003';
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

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata($gather_types, $song_info)
    {
        // Music metadata only
        if (!in_array('music', $gather_types)) {
            return null;
        }
    
        if (!$mbid = $song_info['mb_trackid']) {
            return null;
        }

        $mb       = new MusicBrainz(new RequestsHttpAdapter());
        $includes = array(
            'artists',
            'releases'
        );
        try {
            $track = $mb->lookup('recording', $mbid, $includes);
        } catch (Exception $e) {
            return null;
        }

        $results = array();

        if (count($track->{'artist-credit'}) > 0) {
            $artist                 = $track->{'artist-credit'}[0];
            $artist                 = $artist->artist;
            $results['mb_artistid'] = $artist->id;
            $results['artist']      = $artist->name;
            $results['title']       = $track->title;
            if (count($track->releases) == 1) {
                $release          = $track->releases[0];
                $results['album'] = $release->title;
            }
        }

        return $results;
    } // get_metadata
} // end AmpacheMusicBrainz
;
