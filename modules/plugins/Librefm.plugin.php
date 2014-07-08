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

class Ampachelibrefm {

    public $name        ='Libre.FM';
    public $description    ='Records your played songs to your Libre.FM Account';
    public $url        ='';
    public $version        ='000002';
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
        if (Preference::exists('librefm_user')) { return false; }

        Preference::insert('librefm_user','Libre.FM Username','','25','string','plugins');
        Preference::insert('librefm_md5_pass','Libre.FM Password','','25','string','plugins');
        Preference::insert('librefm_port','Libre.FM Submit Port','','25','string','internal');
        Preference::insert('librefm_host','Libre.FM Submit Host','','25','string','internal');
        Preference::insert('librefm_url','Libre.FM Submit URL','','25','string','internal');
        Preference::insert('librefm_challenge','Libre.FM Submit Challenge','','25','string','internal');

        return true;

    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall() {

        Preference::delete('librefm_md5_pass');
        Preference::delete('librefm_user');
        Preference::delete('librefm_url');
        Preference::delete('librefm_host');
        Preference::delete('librefm_port');
        Preference::delete('librefm_challenge');

    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade() {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version < 2) {
            Preference::rename('librefm_pass', 'librefm_md5_pass');
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
        
        // Before we start let's pull the last song submitted by this user
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
        $scrobbler = new scrobbler($this->username,$this->password,$this->hostname,$this->port,$this->path,$this->challenge,'turtle.libre.fm');

        // Check to see if the scrobbling works
        if (!$scrobbler->queue_track($song->f_artist_full,$song->f_album_full,$song->title,time(),$song->time,$song->track)) {
            // Depending on the error we might need to do soemthing here
            return false;
        }

        // Go ahead and submit it now
        if (!$scrobbler->submit_tracks()) {
            debug_event($this->name,'Error Submit Failed: ' . $scrobbler->error_msg,'3');
            if ($scrobbler->reset_handshake) {
                debug_event($this->name, 'Re-running Handshake due to error', '1');
                $this->set_handshake($this->user_id);
                // Try try again
                if ($scrobbler->submit_tracks()) {
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

        $scrobbler = new scrobbler($this->username,$this->password,'','','','','turtle.libre.fm');
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
        Preference::update('librefm_port',$user_id,$data['submit_port']);
        Preference::update('librefm_host',$user_id,$data['submit_host']);
        Preference::update('librefm_url',$user_id,$data['submit_url']);
        Preference::update('librefm_challenge',$user_id,$data['challenge']);

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

        if (strlen(trim($data['librefm_user']))) {
            $this->username = trim($data['librefm_user']);
        }
        else {
            debug_event($this->name,'No Username, not scrobbling','3');
            return false;
        }
        if (strlen(trim($data['librefm_md5_pass']))) {
            $this->password = trim($data['librefm_md5_pass']);
        }
        else {
            debug_event($this->name,'No Password, not scrobbling','3');
            return false;
        }

        $this->user_id = $user->id;

        // If we don't have the other stuff try to get it before giving up
        if (!$data['librefm_host'] || !$data['librefm_port'] || !$data['librefm_url'] || !$data['librefm_challenge']) {
            debug_event($this->name,'Running Handshake, missing information','3');
            if (!$this->set_handshake($this->user_id)) {
                debug_event($this->name,'Handshake failed, you lose','3');
                return false;
            }
        }
        else {
            $this->hostname = $data['librefm_host'];
            $this->port = $data['librefm_port'];
            $this->path = $data['librefm_url'];
            $this->challenge = $data['librefm_challenge'];
        }

        return true;

    } // load

} // end Ampachelibrefm
?>
