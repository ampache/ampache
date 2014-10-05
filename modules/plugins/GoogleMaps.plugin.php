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

class AmpacheGoogleMaps {
    
    public $name        = 'GoogleMaps';
    public $categories  = 'geolocation';
    public $description = 'Geolocation analyze with GoogleMaps';
    public $url         = 'http://maps.google.com';
    public $version     = '000001';
    public $min_ampache = '370022';
    public $max_ampache = '999999';

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
        if (Preference::exists('gmaps_api_key')) { return false; }
        Preference::insert('gmaps_api_key','GoogleMaps api key','','75','string','plugins');
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall() {
        Preference::delete('gmaps_api_key');
        return true;
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade() {
        return true;
    } // upgrade

    public function get_location_name($latitude, $longitude) {
        $name = "";
        try {
            $url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=" . $latitude . "," . $longitude . "&sensor=false";
            $request = Requests::get($url);

            $place = json_decode($request->body, true);
            if (count($place['results']) > 0) {
                $name = $place['results'][0]['formatted_address'];
            }
        } catch (Exception $e) {
            debug_event('gmaps', 'Error getting location name: ' . $e->getMessage(), 1);
        }
        
        return $name;
    }
    
    /**
     * load
     * This loads up the data we need into this object, this stuff comes 
     * from the preferences.
     */
    public function load($user) {
        $user->set_preferences();
        $data = $user->prefs;
        
        if (strlen(trim($data['gmaps_api_key']))) {
            $this->api_key = trim($data['gmaps_api_key']);
        }
        
        return true;
    } // load
}
