<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

class AmpacheGooglePlus {
    
    public $name        = 'GooglePlus';
    public $categories  = 'share';
    public $description = 'Google+ Share';
    public $url         = 'https://plus.google.com';
    public $version     = '000001';
    public $min_ampache = '370027';
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
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall() {
        return true;
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade() {
        return true;
    } // upgrade

    public function external_share($url, $text)
    {
        $share = "https://plus.google.com/share";
        $share .= "?url=" . rawurlencode($url);
        
        return $share;
    }
    
    /**
     * load
     * This loads up the data we need into this object, this stuff comes 
     * from the preferences.
     */
    public function load($user) {
        $user->set_preferences();
        $data = $user->prefs;
        
        return true;
    } // load
}
