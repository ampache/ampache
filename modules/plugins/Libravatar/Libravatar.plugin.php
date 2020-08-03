<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

class AmpacheLibravatar
{
    public $name        = 'Libravatar';
    public $categories  = 'avatar';
    public $description = 'Users avatar\'s with Libravatar';
    public $url         = 'https://www.libravatar.org';
    public $version     = '000001';
    public $min_ampache = '360040';
    public $max_ampache = '999999';

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_("Users avatar's with Libravatar");

        return true;
    } // constructor

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        return true;
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        return true;
    } // upgrade

    /**
     * @param $user
     * @param integer $size
     * @return string
     */
    public function get_avatar_url($user, $size = 80)
    {
        $url = "";
        if (!empty($user->email)) {
            // Federated Servers are not supported here without libravatar.org. Should query DNS server first.
            if (filter_has_var(INPUT_SERVER, 'HTTPS') && Core::get_server('HTTPS') !== 'off') {
                $url = "https://seccdn.libravatar.org";
            } else {
                $url = "http://cdn.libravatar.org";
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
     * @param User $user
     * @return boolean
     */
    public function load($user)
    {
        $user->set_preferences();

        return true;
    } // load
} // end AmpacheLibravatar
