<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

class AmpacheMusicBrainz {

    public $name        ='MusicBrainz';
    public $description    ='MusicBrainz metadata integration';
    public $version        ='000001';
    public $min_ampache    ='360003';
    public $max_ampache    ='999999';

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
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall() {
        return true;
    } // uninstall

    /**
     * load
     * This is a required plugin function; here it populates the prefs we 
     * need for this object.
     */
    public function load() {
        return true;
    } // load

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata($song_info) {
        if (!$mbid = $song_info['mb_trackid']) {
            return null;
        }

        $mbquery = new MusicBrainzQuery();
        $includes = new mbTrackIncludes();
        $includes = $includes->artist()->releases();
        try {
            $track = $mbquery->getTrackById($mbid, $includes);
        }
        catch (Exception $e) {
            return null;
        }

        $results = array();

        $results['mb_artistid'] = $track->getArtist()->getId();
        $results['artist'] = $track->getArtist()->getName();
        $results['title'] = $track->getTitle();
        if ($track->getNumReleases() == 1) {
            $release = $track->getReleases();
            $release = $release[0];
            $results['album'] = $release->getTitle();
        }

        return $results;
    } // get_metadata

} // end AmpacheMusicBrainz
?>
