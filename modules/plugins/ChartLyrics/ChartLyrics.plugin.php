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

class Ampachechartlyrics
{
    public $name           = 'ChartLyrics';
    public $categories     = 'lyrics';
    public $description    = 'Get lyrics from ChartLyrics';
    public $url            = 'http://www.chartlyrics.com';
    public $version        ='000001';
    public $min_ampache    ='360022';
    public $max_ampache    ='999999';

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('Get lyrics from ChartLyrics');

        return true;
    } // constructor

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
     * get_lyrics
     * This will look web services for a song lyrics.
     * @param Song $song
     * @return array|boolean
     */
    public function get_lyrics($song)
    {
        $base    = 'http://api.chartlyrics.com/apiv1.asmx/';
        $uri     = $base . 'SearchLyricDirect?artist=' . urlencode($song->f_artist) . '&song=' . urlencode($song->title);
        $request = Requests::get($uri, array(), Core::requests_options());
        if ($request->status_code == 200) {
            $xml = simplexml_load_string($request->body);
            if ($xml) {
                if (!empty($xml->Lyric)) {
                    return array('text' => nl2br($xml->Lyric), 'url' => $xml->LyricUrl);
                }
            }
        }

        return false;
    } // get_lyrics
} // end Ampachelyricwiki
