<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

class AmpacheFlickr {

    public $name        = 'Flickr';
    public $description = 'Artist photos from Flickr';
    public $url         = 'http://www.flickr.com';
    public $version     = '000001';
    public $min_ampache = '360045';
    public $max_ampache = '999999';

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
        Preference::insert('flickr_api_key','Flickr api key','','25','string','plugins');
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall() {
        Preference::delete('flickr_api_key');
        return true;
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade() {
        return true;
    } // upgrade

    public function get_photos($search) {
        $photos = array();
        $url = "https://api.flickr.com/services/rest/?&method=flickr.photos.search&api_key=" . $this->api_key . "&per_page=20&content_type=1&text=" . rawurlencode($search . " music");
        $request = Requests::get($url);
        if ($request->status_code == 200) {
            $xml = simplexml_load_string($request->body);
            if ($xml) {
                foreach ($xml->photos->photo as $photo) {
                    $photos[] = array(
                        'title' => $photo->title,
                        'url' => "http://farm" . $photo['farm'] . ".static.flickr.com/" . $photo['server'] . "/" . $photo['id'] . "_" . $photo['secret'] . "_m.jpg",
                    );
                }
            }
        }
        
        return $photos;
    }
    
    /**
     * load
     * This loads up the data we need into this object, this stuff comes 
     * from the preferences.
     */
    public function load($user) {
        $user->set_preferences();
        $data = $user->prefs;
        
        if (strlen(trim($data['flickr_api_key']))) {
            $this->api_key = trim($data['flickr_api_key']);
        }
        else {
            debug_event($this->name,'No Flickr api key, photo plugin skipped','3');
            return false;
        }
        return true;
    } // load

} // end AmpacheFlickr
?>
