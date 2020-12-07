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

class AmpachePersonalFavorites
{
    public $name           = 'Personal Favorites';
    public $categories     = 'home';
    public $description    = 'Personal favorites on homepage';
    public $url            = '';
    public $version        = '000002';
    public $min_ampache    = '370021';
    public $max_ampache    = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $display;
    private $playlist;
    private $smartlist;
    private $user;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('Personal favorites on homepage');

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
        if (Preference::exists('personalfav_display')) {
            return false;
        }

        Preference::insert('personalfav_display', T_('Personal favorites on the homepage'), '0', 25, 'boolean', 'plugins', $this->name);
        Preference::insert('personalfav_playlist', T_('Favorite Playlists'), '', 25, 'integer', 'plugins', $this->name);
        Preference::insert('personalfav_smartlist', T_('Favorite Smartlists'), '', 25, 'integer', 'plugins', $this->name);

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function. It removes our preferences from
     * the database returning it to its original form
     */
    public function uninstall()
    {
        Preference::delete('personalfav_display');
        Preference::delete('personalfav_playlist');
        Preference::delete('personalfav_smartlist');
        Preference::delete('personalfav_gridview');

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
        // display if you've enabled it
        if ($this->display) {
            $list_array = array();
            foreach (explode(',', $this->playlist) as $list_id) {
                $playlist     = new Playlist((int) $list_id);
                $list_array[] = array($playlist, 'playlist');
            }
            foreach (explode(',', $this->smartlist) as $list_id) {
                $smartlist    = new Search((int) $list_id);
                $list_array[] = array($smartlist, 'search');
            }
            if (!empty($list_array)) {
                echo '<div class="home_plugin">';
                UI::show_box_top(T_('Favorite Lists'));
                echo '<table class="tabledata';
                echo " disablegv";
                echo '">';
                $count = 0;
                foreach ($list_array as $item) {
                    $item[0]->format();
                    $this->user->format();

                    if ($item[0]->id) {
                        echo '<tr id="playlist_' . $item[0]->id . '" class="' . ((($count % 2) == 0) ? 'even' : 'odd') . ' libitem_menu">';
                        echo '<td>' . $item[0]->f_link . '</td>';
                        echo '<td style="height: auto;">';
                        echo '<span style="margin-right: 10px;">';
                        if (AmpConfig::get('directplay')) {
                            echo Ajax::button('?page=stream&action=directplay&object_type=' . $item[1] . '&object_id=' . $item[0]->id, 'play', T_('Play'), 'play_playlist_' . $item[0]->id);
                            if (Stream_Playlist::check_autoplay_next()) {
                                echo Ajax::button('?page=stream&action=directplay&object_type=' . $item[1] . '&object_id=' . $item[0]->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_playlist_' . $item[0]->id);
                            }
                            if (Stream_Playlist::check_autoplay_append()) {
                                echo Ajax::button('?page=stream&action=directplay&object_type=' . $item[1] . '&object_id=' . $item[0]->id . '&append=true', 'play_add', T_('Play last'), 'addplay_playlist_' . $item[0]->id);
                            }
                        }
                        echo Ajax::button('?action=basket&type=' . $item[1] . '&id=' . $item[0]->id, 'add', T_('Add to temporary playlist'), 'play_full_' . $item[0]->id);
                        echo '</span></td>';
                        echo '<td class="optional">';
                        echo '<div style="white-space: normal;">' . $item[0]->description . '</div>';
                        echo '</div>';
                        echo '</td></tr>';

                        $count++;
                    }
                }
                echo '</table>';
                UI::show_box_bottom();
                echo '</div>';
            }
        }
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     * @param User $user
     * @return boolean
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->user      = $user;
        $this->display   = ($data['personalfav_display'] == '1');
        $this->playlist  = $data['personalfav_playlist'];
        $this->smartlist = $data['personalfav_smartlist'];

        return true;
    }
}
