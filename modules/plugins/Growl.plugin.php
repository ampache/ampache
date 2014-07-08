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
 
class Ampachegrowl {

    public $name           = 'Growl';
    public $description    = 'Send your played songs notification to Growl';
    public $url            = '';
    public $version        ='000001';
    public $min_ampache    ='360003';
    public $max_ampache    ='999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $address;
    private $password;
    private $message;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct() {

        require_once AmpConfig::get('prefix') . '/modules/growl/growl.gntp.php';
        
        return true;

    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install() {
        if (Preference::exists('growl_address')) { return false; }

        Preference::insert('growl_address','Growl server address','127.0.0.1','25','string','plugins');
        Preference::insert('growl_pass','Growl password','','25','string','plugins');
        Preference::insert('growl_message','Growl notification message','%user now listening %artist - %title','25','string','plugins');
        Preference::insert('growl_registered_address','Growl registered address','','25','string','internal');

        return true;

    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall() {

        Preference::delete('growl_address');
        Preference::delete('growl_pass');
        Preference::delete('growl_message');
        Preference::delete('growl_registered_address');

        return true;
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade() {
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
        $user = new User($this->user_id);

        $diff = time() - $previous['date'];

        // Make sure it wasn't within the last 10sec
        if ($diff < 10) {
            debug_event($this->name, 'Last song played within ' . $diff . ' seconds, not recording stats', '3');
            return false;
        }

        // Make sure there's actually a server address before we keep going
        if (!$this->address) {
            debug_event($this->name, 'Server address missing', '3');
            return false;
        }
        
        $message = str_replace("%user", $user->fullname, $this->message);
        $message = str_replace("%artist", $song->f_artist_full, $message);
        $message = str_replace("%album", $song->f_album_full, $message);
        $message = str_replace("%title", $song->title, $message);

        $growl = $this->get_growl();
        $growl->notify('Now Playing', $message);

        debug_event($this->name, 'Submission Successful', '5');

        return true;

    } // submit
    
    public function get_growl()
    {
        $growl = new Growl($this->address, $this->password);
        $growl->setApplication('Ampache', 'Now Playing');
        return $growl;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes 
     * from the preferences.
     */
    public function load($user) {

        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['growl_address']))) {
            $this->address = trim($data['growl_address']);
        }
        else {
            debug_event($this->name,'No server address, not scrobbling','3');
            return false;
        }
        $this->password = trim($data['growl_pass']);
        $this->message = trim($data['growl_message']);
        
        $registered_address = trim($data['growl_registered_address']);
        $confhash = md5($this->address . ':' . $this->password);
        if ($registered_address != $confhash) {
            $growl = $this->get_growl();
            $icon = AmpConfig::get('theme_path') . '/images/ampache.png';
            $growl->registerApplication($icon);
            
            debug_event($this->name, 'Growl registered.', '5');            
            Preference::update('growl_registered_address', $user->id, $confhash);
        }

        $this->user_id = $user->id;

        return true;

    } // load

} // end Ampachegrowl
?>
