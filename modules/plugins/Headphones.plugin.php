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

class AmpacheHeadphones {

    public $name        = 'Headphones';
    public $categories  = 'misc,wanted';
    public $description = 'Automatically download accepted Wanted List albums with Headphones';
    public $url         = 'https://github.com/rembo10/headphones/';
    public $version     = '000001';
    public $min_ampache = '360030';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $api_url;
    private $api_key;

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
        if (Preference::exists('headphones_api_url')) { return false; }

        Preference::insert('headphones_api_url','Headphones url','','25','string','plugins');
        Preference::insert('headphones_api_key','Headphones api key','','25','string','plugins');

        return true;

    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall() {

        Preference::delete('headphones_api_url');
        Preference::delete('headphones_api_key');
        
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
     * process_wanted
     * This takes care of auto-download accepted Wanted List albums
     */
    public function process_wanted($wanted) {       
        
        set_time_limit(0);
        
        $artist = new Artist($wanted->artist);
        $headartist = json_decode($this->headphones_call('getArtist', array(
            'id' => $artist->mbid
        )));
        
        // No artist info, need to add artist to Headphones first. Can be long!
        if (count($headartist->artist) == 0) {
            $this->headphones_call('addArtist', array(
                'id' => $artist->mbid
            ));
        }
        
        return ($this->headphones_call('queueAlbum', array(
            'id' => $wanted->mbid
        )) == 'OK');
    
    } // process_wanted

    protected function headphones_call($command, $params) {
        
        if (empty($this->api_url) || empty($this->api_key)) {
            debug_event($this->name, 'Headphones url or api key missing', '3');
            return false;
        }
    
        $url = $this->api_url . '/api?apikey=' . $this->api_key . '&cmd=' . $command;
        foreach ($params as $key => $value) {
            $url .= '&' . $key . '=' . urlencode($value);
        }
        
        debug_event($this->name, 'Headphones api call: ' . $url, '5');
        try {
            $request = Requests::get($url, array(), array(
                'timeout' => 600
            ));
        } catch (Exception $e) {
            debug_event($this->name, 'Headphones api http exception: ' . $e->getMessage(), '1');
            return false;
        }
        
        return $request->body;
    }
    
    /**
     * load
     * This loads up the data we need into this object, this stuff comes 
     * from the preferences.
     */
    public function load($user) {

        $user->set_preferences();
        $data = $user->prefs;

        if (strlen(trim($data['headphones_api_url']))) {
            $this->api_url = trim($data['headphones_api_url']);
        }
        else {
            debug_event($this->name,'No Headphones url, auto download skipped','3');
            return false;
        }
        if (strlen(trim($data['headphones_api_key']))) {
            $this->api_key = trim($data['headphones_api_key']);
        }
        else {
            debug_event($this->name,'No Headphones api key, auto download skipped','3');
            return false;
        }

        return true;

    } // load

} // end AmpacheHeadphones
?>
