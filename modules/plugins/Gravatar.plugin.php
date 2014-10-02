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

class AmpacheGravatar {

    public $name        = 'Gravatar';
    public $categories  = 'avatar';
    public $description = 'Users avatars with Gravatar';
    public $url         = 'http://gravatar.com';
    public $version     = '000001';
    public $min_ampache = '360040';
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

    public function get_avatar_url($user, $size = 80) {
        
        $url = "";
        if (!empty($user->email)) {
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                $url = "https://secure.gravatar.com";
            } else {
                $url = "http://www.gravatar.com";
            }
            $url .= "/avatar/";
            $url .= md5(strtolower(trim($user->email)));
            $url .= "?s=" . $size . "&r=g";
            $url .= "&d=identicon";
        }
        
        return $url;
    }
    
    /**
     * load
     * This loads up the data we need into this object, this stuff comes 
     * from the preferences.
     */
    public function load($user) {
        return true;
    } // load

} // end AmpacheGravatar
?>
