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

class Ampachelibrefm
{
    public $name        = 'Libre.FM';
    public $categories  = 'scrobbling';
    public $description = 'Records your played songs to your Libre.FM Account';
    public $url;
    public $version     = '000003';
    public $min_ampache = '360003';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $challenge;
    private $user_id;
    private $api_key;
    private $secret;
    private $scheme     = 'https';
    private $host       = 'libre.fm';
    private $api_host   = 'libre.fm';

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->url = $this->scheme . '://' . $this->host;

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
        if (Preference::exists('librefm_user')) {
            return false;
        }

        Preference::insert('librefm_challenge', 'Libre.FM Submit Challenge', '', '25', 'string', 'internal', $this->name);
        Preference::insert('librefm_grant_link', 'Libre.FM Grant URL', '', '25', 'string', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('librefm_challenge');
        Preference::delete('librefm_grant_link');
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version < 2) {
            Preference::rename('librefm_pass', 'librefm_md5_pass');
        }
        if ($from_version < 3) {
            Preference::delete('librefm_md5_pass');
            Preference::delete('librefm_user');
            Preference::delete('librefm_url');
            Preference::delete('librefm_host');
            Preference::delete('librefm_port');
            Preference::insert('librefm_grant_link', 'Libre.FM Grant URL', '', '25', 'string', 'plugins');
        }

        return true;
    } // upgrade

    /**
     * save_songplay
     * This takes care of queueing and then submitting the tracks.
     */
    public function save_mediaplay($song)
    {
        // Only support songs
        if (strtolower(get_class($song)) != 'song') {
            return false;
        }
        
        // Make sure there's actually a session before we keep going
        if (!$this->challenge) {
            debug_event($this->name, 'Session key missing', '5');

            return false;
        }

        // Before we start let's pull the last song submitted by this user
        $previous = Stats::get_last_song($this->user_id);

        $diff = time() - $previous['date'];

        // Make sure it wasn't within the last min
        if ($diff < 60) {
            debug_event($this->name, 'Last song played within ' . $diff . ' seconds, not recording stats', '3');

            return false;
        }

        if ($song->time < 30) {
            debug_event($this->name, 'Song less then 30 seconds not queueing', '3');

            return false;
        }

        // Create our scrobbler and then queue it
        $scrobbler = new scrobbler($this->api_key, $this->scheme, $this->api_host, $this->challenge, $this->secret);

        // Check to see if the scrobbling works by queueing song
        if (!$scrobbler->queue_track($song->f_artist_full, $song->f_album_full, $song->title, time(), $song->time, $song->track)) {
            return false;
        }

        // Go ahead and submit it now
        if (!$scrobbler->submit_tracks()) {
            debug_event($this->name, 'Error Submit Failed: ' . $scrobbler->error_msg, '3');

            return false;
        }

        debug_event($this->name, 'Submission Successful', '5');

        return true;
    } // submit

    /**
     * set_flag
     * This takes care of spreading your love on Libre.fm
     */
    public function set_flag($song, $flagged)
    {
        // Make sure there's actually a session before we keep going
        if (!$this->challenge) {
            debug_event($this->name, 'Session key missing', '5');

            return false;
        }
        // Create our scrobbler and then queue it
        $scrobbler = new scrobbler($this->api_key, $this->scheme, $this->api_host, $this->challenge, $this->secret);
        if (!$scrobbler->love($flagged, 'song', $song->f_artist_full, $song->title, $song->f_album_full)) {
            debug_event($this->name, 'Error Love Failed: ' . $scrobbler->error_msg, '3');

            return false;
        }
        debug_event($this->name, 'Sent Love Successfully', '5');

        return true;
    } // set_flag

    /**
     * get_session
     * This call the getSession method and properly updates the preferences as needed.
     * This requires a userid so it knows whose crap to update.
     */
    public function get_session($user_id, $token)
    {
        $scrobbler   = new scrobbler($this->api_key, $this->scheme, $this->api_host, '', $this->secret);
        $session_key = $scrobbler->get_session_key($token);
        if (!$session_key) {
            debug_event($this->name, 'getSession Failed: ' . $scrobbler->error_msg, '3');

            return false;
        }
        $this->challenge = $session_key;

        // Update the preferences
        Preference::update('librefm_challenge', $user_id, $session_key);
        debug_event($this->name, 'getSession Successful', '3');

        return true;
    } // get_session

    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     */
    public function load($user)
    {
        $this->api_key=AmpConfig::get('lastfm_api_key');
        $this->secret ='';
        $user->set_preferences();
        $data          = $user->prefs;
        $this->user_id = $user->id;
        // check if user have a session key
        if (strlen(trim($data['librefm_challenge']))) {
            $this->challenge= trim($data['librefm_challenge']);
        } else {
            debug_event($this->name, 'No session key, not scrobbling (need to grant Ampache to libre.fm)', '5');

            return false;
        }

        return true;
    } // load
} // end Ampachelibrefm
;
