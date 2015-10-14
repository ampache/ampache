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

class AmpacheCatalogFavorites
{
    public $name           = 'Catalog Favorites';
    public $categories     = 'home';
    public $description    = 'Catalog favorites on homepage';
    public $url            = '';
    public $version        = '000002';
    public $min_ampache    = '370021';
    public $max_ampache    = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $maxitems;

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
        if (Preference::exists('catalogfav_max_items')) {
            return false;
        }

        Preference::insert('catalogfav_max_items','Catalog favorites max items','5','25','integer','plugins');
        Preference::insert('catalogfav_columns','Catalog favorites columns','1','25','integer','plugins');

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('catalogfav_max_items');

        return true;
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version < 2) {
            Preference::insert('catalogfav_columns','Catalog favorites columns','1','25','integer','plugins');
        }
        return true;
    }

    /**
     * display_home
     * This display the module in home page
     */
    public function display_home()
    {
        if (AmpConfig::get('userflags')) {
            $userflags = Userflag::get_latest(null, -1, $this->maxitems);
            $i = 0;
            echo '<div class="home_plugin"><table class="tabledata">';
            foreach ($userflags as $userflag) {
                $item = new $userflag['type']($userflag['id']);
                $item->format();
                $user = new User($userflag['user']);
                $user->format();
                
                if ($item->id) {
                    echo '<tr class="' . ((($i % 2) == 0) ? 'even' : 'odd') . '"><td>';
                    echo '<div>';
                    echo '<div style="float: left;">';
                    echo '<span style="font-weight: bold;">' . $item->f_link . '</span> ';
                    echo '<span style="margin-right: 10px;">';
                    if (AmpConfig::get('directplay')) {
                        echo Ajax::button('?page=stream&action=directplay&object_type=' . $userflag['type'] . '&object_id=' . $userflag['id'],'play', T_('Play'),'play_' . $userflag['type'] . '_' . $userflag['id']);
                        if (Stream_Playlist::check_autoplay_append()) {
                            echo Ajax::button('?page=stream&action=directplay&object_type=' . $userflag['type'] . '&object_id=' . $userflag['id'] . '&append=true','play_add', T_('Play last'),'addplay_' . $userflag['type'] . '_' . $userflag['id']);
                        }
                    }
                    echo Ajax::button('?action=basket&type=' . $userflag['type'] . '&id=' . $userflag['id'],'add', T_('Add to temporary playlist'),'play_full_' . $userflag['id']);
                    echo '</span>';
                    echo '</div>';
                    echo '<div style="float: right; opacity: 0.5;">' . T_('recommended by') . ' '. $user->f_link . '</div>';

                    echo '</div><br />';

                    echo '<div style="margin-left: 30px;">';
                    echo '<div style="float: left; margin-right: 20px;">';
                    $item->display_art(2);
                    echo '</div>';

                    echo '<div style="white-space: normal;">'. $item->get_description() .'</div>';
                    echo '</div>';

                    echo '</td></tr>';

                    $i++;
                }
            }
            echo '</table></div>';
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

        $this->maxitems = intval($data['catalogfav_max_items']);
        if ($this->maxitems < 1) {
            $this->maxitems = 5;
        }

        return true;
    }
}
