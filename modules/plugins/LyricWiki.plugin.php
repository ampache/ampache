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

class Ampachelyricwiki {

    public $name           = 'LyricWiki';
    public $categories     = 'lyrics';
    public $description    = 'Get lyrics from LyricWiki';
    public $url            = 'http://lyrics.wikia.com/Lyrics_Wiki';
    public $version        ='000001';
    public $min_ampache    ='360022';
    public $max_ampache    ='999999';

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct() {

        return true;

    } // constructor

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
    public function load($user) {
        return true;
    } // load

    /**
     * get_lyrics
     * This will look web services for a song lyrics.
     */
    public function get_lyrics($song) {
  
        $uri = 'http://lyrics.wikia.com/api.php?action=lyrics&artist=' . urlencode($song->f_artist) . '&song=' . urlencode($song->title) . '&fmt=xml&func=getSong';
        $request = Requests::get($uri);
        if ($request->status_code == 200) {
            $xml = simplexml_load_string($request->body);
            if ($xml) {
                if (!empty($xml->lyrics) && $xml->lyrics != "Not found") {
                    return array('text' => nl2br($xml->lyrics), 'url' => $xml->url);
                }
            }
        }
        
        return false;

    } // get_lyrics

} // end Ampachelyricwiki
?>
