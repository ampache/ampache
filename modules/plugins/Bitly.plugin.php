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

class AmpacheBitly {

    public $name        = 'Bit.ly';
    public $description = 'Url shorteners on shared links with Bit.ly';
    public $url         = 'http://bitly.com/';
    public $version     = '000002';
    public $min_ampache = '360037';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $bitly_username;
    private $bitly_api_key;

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
        if (Preference::exists('bitly_username')) { return false; }

        Preference::insert('bitly_username','Bit.ly username','','75','string','plugins');
        Preference::insert('bitly_api_key','Bit.ly api key','','75','string','plugins');

        return true;

    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall() {

        Preference::delete('bitly_username');
        Preference::delete('bitly_api_key');

    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade() {
        return true;
    } // upgrade

    public function shortener($url) {
        
        if (empty($this->bitly_username) || empty($this->bitly_api_key)) {
            debug_event($this->name, 'Bit.ly username or api key missing', '3');
            return false;
        }
        
        $shorturl = '';
    
        $apiurl = 'http://api.bit.ly/v3/shorten?login=' . $this->bitly_username . '&apiKey=' . $this->bitly_api_key . '&longUrl=' . urlencode($url) . '&format=json';
        try {
            debug_event($this->name, 'Bit.ly api call: ' . $apiurl, '5');
            $request = Requests::get($apiurl);
            $shorturl = json_decode($request->body)->data->url;
        } catch (Exception $e) {
            debug_event($this->name, 'Bit.ly api http exception: ' . $e->getMessage(), '1');
            return false;
        }
        
        return $shorturl;
    }
    
    /**
     * load
     * This loads up the data we need into this object, this stuff comes 
     * from the preferences.
     */
    public function load($user) {

        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['bitly_username']))) {
            $this->bitly_username = trim($data['bitly_username']);
        }
        else {
            debug_event($this->name,'No Bit.ly username, shortener skipped','3');
            return false;
        }
        if (strlen(trim($data['bitly_api_key']))) {
            $this->bitly_api_key = trim($data['bitly_api_key']);
        }
        else {
            debug_event($this->name,'No Bit.ly api key, shortener skipped','3');
            return false;
        }

        return true;

    } // load

} // end AmpacheBitly
?>
