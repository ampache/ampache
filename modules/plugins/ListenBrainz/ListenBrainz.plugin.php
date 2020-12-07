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

class Ampachelistenbrainz
{
    public $name        = 'ListenBrainz';
    public $categories  = 'scrobbling';
    public $description = 'Records your played songs to your ListenBrainz Account';
    public $url;
    public $version     = '000001';
    public $min_ampache = '380004';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $token;
    private $user_id;
    private $scheme     = 'https';
    private $host       = 'listenbrainz.org';
    private $api_host   = 'api.listenbrainz.org';

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('Scrobble songs you play to your ListenBrainz Account');
        $this->url         = $this->scheme . '://' . $this->host;

        return true;
    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {

        // Check and see if it's already installed (they've just hit refresh, those dorks)
        if (Preference::exists('listenbrainz_token')) {
            return false;
        }

        Preference::insert('listenbrainz_token', T_('ListenBrainz User Token'), '', 25, 'string', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('listenbrainz_token');
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        return true;
    } // upgrade

    /**
     * save_mediaplay
     * This takes care of queuing and then submitting the tracks.
     * @param Song $song
     * @return boolean
     */
    public function save_mediaplay($song)
    {
        // Only support songs
        if (get_class($song) != 'Song') {
            return false;
        }

        // Make sure there's actually a token before we keep going
        if (!$this->token) {
            debug_event('listenbrainz.plugin', 'Token missing', 5);

            return false;
        }
        if ($song->time < 30) {
            debug_event('listenbrainz.plugin', 'Song less then 30 seconds not queueing', 3);

            return false;
        }

        $album  = new Album($song->album);
        $artist = new Artist($song->artist);

        $additional_info = array();
        if ($song->mbid) {
            $additional_info['recording_mbid'] = $song->mbid;
        }
        if ($album->mbid) {
            $additional_info['release_mbid'] = $album->mbid;
        }
        if ($artist->mbid) {
            $additional_info['artist_mbid'] = $artist->mbid;
        }
        $track_metadata = array(
                'additional_info' => $additional_info,
                'artist_name' => $artist->name,
                'track_name' => $song->title,
                'release_name' => $album->name,
            );
        if (empty($additional_info)) {
            $track_metadata = array_splice($track_metadata, 1);
        }
        $json = json_encode(array(
            'listen_type' => 'single',
            'payload' => array(
                array(
                    'listened_at' => time(),
                    'track_metadata' => $track_metadata
                )
            )
        ));
        debug_event('listenbrainz.plugin', 'Submission content: ' . $json, 5);
        $response = $this->post_json_url('/1/submit-listens', $json);

        if (!strpos($response, "ok")) {
            debug_event('listenbrainz.plugin', "Submission Failed", 5);

            return false;
        }
        debug_event('listenbrainz.plugin', "Submission Successful", 5);

        return true;
    } // submit

    /**
     * post_json_url
     * This is a generic poster for HTTP requests
     * @param string $url
     * @param string $content
     * @return false|string
     */
    private function post_json_url($url, $content)
    {
        $opts = array(
                'http' => array(
                        'method' => 'POST',
                        'header' => array(
                                'Authorization: token ' . $this->token,
                                'Content-type: application/json; charset=utf-8',
                                'Content-length: ' . strlen($content)
                        ),
                        'content' => $content
                )
        );
        debug_event('listenbrainz.plugin', 'Submission option: ' . json_encode($opts), 5);
        $context = stream_context_create($opts);
        $target  = $this->scheme . '://' . $this->api_host . $url;

        return file_get_contents($target, false, $context);
    } // call_url

    /**
     * set_flag
     * This takes care of spreading your love on ListenBrainz
     * @param Song $song
     * @param boolean $flagged
     * @return boolean
     */
    public function set_flag($song, $flagged)
    {
        return true;
    } // set_flag

    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     * @param User $user
     * @return boolean
     */
    public function load($user)
    {
        $user->set_preferences();
        $data          = $user->prefs;
        $this->user_id = $user->id;
        // check if user have a token
        if (strlen(trim($data['listenbrainz_token']))) {
            $this->token = trim($data['listenbrainz_token']);
        } else {
            debug_event('listenbrainz.plugin', 'No token, not scrobbling (need to add your ListenBrainz api key to ampache)', 4);

            return false;
        }

        return true;
    } // load
} // end Ampachelibrefm
