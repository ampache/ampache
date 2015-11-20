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

class AmpacheHomeMessage
{
    public $name           = 'Home Message';
    public $categories     = 'home';
    public $description    = 'Message on homepage';
    public $url            = '';
    public $version        = '000001';
    public $min_ampache    = '370040';
    public $max_ampache    = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $message;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        return true;
    }

    /**
     * install
     * This is a required plugin function. It inserts our preferences
     * into Ampache
     */
    public function install()
    {
        // Check and see if it's already installed
        if (Preference::exists('hm_message')) {
            return false;
        }

        Preference::insert('hm_message','Home Message text','','75','string','plugins');
		Preference::insert('hm_level','Home Message level','3','75','integer','plugins');

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('hm_message');
		Preference::delete('hm_level');

        return true;
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        return true;
    }

    /**
     * display_home
     * This display the module in home page
     */
    public function display_home()
    {
        if (!empty($this->message)) {
            UI::show_box_top(T_('Global Message'));
			$dstyle = '';
			switch ($this->level)
			{
				case 1:
					$dstyle .= 'color: #C33; font-weight: bold;';
					break;
				case 2:
					$dstyle .= 'color: #FF0; font-weight: bold;';
					break;
				default:
					$dstyle .= '';
					break;
			}
			echo '<div style="' . $dstyle . '">';
			echo $this->message;
			echo '</div>';
			UI::show_box_bottom();
        }
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes
     * from the preferences.
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->message = $data['hm_message'];
		$this->level = $data['hm_level'];
        

        return true;
    }
}
