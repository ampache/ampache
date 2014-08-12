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

class AmpacheLastfm {

    public $name        ='Last.FM';
    public $description    ='Records your played songs to your Last.FM Account';
    public $url        ='';
    public $version        ='000004';
    public $min_ampache    ='360003';
    public $max_ampache    ='999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $username;
    private $password;
    private $hostname;
    private $port;
    private $path;
    private $challenge;
    private $user_id;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct() {

        return true;

    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install() {

        // Check and see if it's already installed (they've just hit refresh, those dorks)
        if (Preference::exists('lastfm_user')) { return false; }

        Preference::insert('lastfm_user','Last.FM Username','','25','string','plugins');
        Preference::insert('lastfm_md5_pass','Last.FM Password','','25','string','plugins');
        Preference::insert('lastfm_port','Last.FM Submit Port','','25','string','internal');
        Preference::insert('lastfm_host','Last.FM Submit Host','','25','string','internal');
        Preference::insert('lastfm_url','Last.FM Submit URL','','25','string','internal');
        Preference::insert('lastfm_challenge','Last.FM Submit Challenge','','25','string','internal');

        return true;

    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall() {

        Preference::delete('lastfm_md5_pass');
        Preference::delete('lastfm_user');
        Preference::delete('lastfm_url');
        Preference::delete('lastfm_host');
        Preference::delete('lastfm_port');
        Preference::delete('lastfm_challenge');

    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade() {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version < 4) {
            Preference::rename('lastfm_pass', 'lastfm_md5_pass');
        }
        return true;
    } // upgrade

    /**
     * save_songplay
     * This takes care of queueing and then submitting the tracks.
     */
    public function save_mediaplay($song) {
        // Only support songs
        if (strtolower(get_class($song)) != 'song') return false;
        
        // Let's pull the last song submitted by this user
        $previous = Stats::get_last_song($this->user_id);

        $diff = time() - $previous['date'];

        // Make sure it wasn't within the last min
        if ($diff < 60) {
            debug_event($this->name,'Last song played within ' . $diff . ' seconds, not recording stats','3');
            return false;
        }

        if ($song->time < 30) {
            debug_event($this->name,'Song less then 30 seconds not queueing','3');
            return false;
        }

        // Make sure there's actually a username and password before we keep going
        if (!$this->username || !$this->password) {
            debug_event($this->name,'Username or password missing','3');
            return false;
        }

        // Create our scrobbler with everything this time and then queue it
        $scrobbler = new scrobbler($this->username,$this->password,$this->hostname,$this->port,$this->path,$this->challenge);

        // Check to see if the scrobbling works
        if (!$scrobbler->queue_track($song->f_artist_full,$song->f_album_full,$song->title,time(),$song->time,$song->track)) {
            // Depending on the error we might need to do soemthing here
            return false;
        }

        // Go ahead and submit it now
        if (!$scrobbler->submit_tracks()) {
            debug_event($this->name,'Error Submit Failed: ' . $scrobbler->error_msg,'3');
            if ($scrobbler->reset_handshake) {
                debug_event($this->name,'Re-running Handshake due to error','3');
                $this->set_handshake($this->user_id);
                // Try try again
                if ($scrobbler->submit_tracks()) {
                    debug_event($this->name,'Submission Successful','5'); 
                    return true;
                }
            }
            return false;
        }

        debug_event($this->name,'Submission Successful','5');

        return true;

    } // submit

    /**
     * set_handshake
     * This runs a handshake and properly updates the preferences as needed.
     * It returns the data as an array so we don't have to requery the db.
     * This requires a userid so it knows whose crap to update.
     */
    public function set_handshake($user_id) {

        $scrobbler = new scrobbler($this->username,$this->password);
        $data = $scrobbler->handshake();

        if (!$data) {
            debug_event($this->name,'Handshake Failed: ' . $scrobbler->error_msg,'3');
            return false;
        }

        $this->hostname = $data['submit_host'];
        $this->port = $data['submit_port'];
        $this->path = $data['submit_url'];
        $this->challenge = $data['challenge'];

        // Update the preferences
        Preference::update('lastfm_port',$user_id,$data['submit_port']);
        Preference::update('lastfm_host',$user_id,$data['submit_host']);
        Preference::update('lastfm_url',$user_id,$data['submit_url']);
        Preference::update('lastfm_challenge',$user_id,$data['challenge']);

        return true;

    } // set_handshake

    /**
     * load
     * This loads up the data we need into this object, this stuff comes 
     * from the preferences.
     */
    public function load($user) {

        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['lastfm_user']))) {
            $this->username = trim($data['lastfm_user']);
        }
        else {
            debug_event($this->name,'No Username, not scrobbling','3');
            return false;
        }
        if (strlen(trim($data['lastfm_md5_pass']))) {
            $this->password = trim($data['lastfm_md5_pass']);
        }
        else {
            debug_event($this->name,'No Password, not scrobbling','3');
            return false;
        }

        $this->user_id = $user->id;

        // If we don't have the other stuff try to get it before giving up
        if (!$data['lastfm_host'] || !$data['lastfm_port'] || !$data['lastfm_url'] || !$data['lastfm_challenge']) {
            debug_event($this->name, 'Running Handshake, missing information', '1');
            if (!$this->set_handshake($this->user_id)) {
                debug_event($this->name, 'Handshake failed, you lose', '3');
                return false;
            }
        }
        else {
            $this->hostname = $data['lastfm_host'];
            $this->port = $data['lastfm_port'];
            $this->path = $data['lastfm_url'];
            $this->challenge = $data['lastfm_challenge'];
        }

        return true;

    } // load

} // end AmpacheLastfm
?>
