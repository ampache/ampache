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
    private $gridview;

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        $this->description = T_('Catalog favorites on homepage');

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

        Preference::insert('catalogfav_max_items', T_('Catalog favorites max items'), 5, 25, 'integer', 'plugins', $this->name);
        Preference::insert('catalogfav_gridview', T_('Catalog favorites grid view display'), '0', 25, 'boolean', 'plugins', $this->name);

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
        Preference::delete('catalogfav_gridview');

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
            Preference::insert('catalogfav_gridview', T_('Catalog favorites grid view display'), '0', 25, 'boolean', 'plugins');
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
            $userflags = Userflag::get_latest(null, 0, $this->maxitems);
            $count     = 0;
            echo '<div class="home_plugin">';
            UI::show_box_top(T_('Highlight'));
            echo '<table class="tabledata';
            if (!$this->gridview) {
                echo " disablegv";
            }
            echo '">';
            foreach ($userflags as $userflag) {
                $item = new $userflag['type']($userflag['id']);
                $item->format();
                $user = new User($userflag['user']);
                $user->format();

                if ($item->id) {
                    echo '<tr id="' . $userflag['type'] . '_' . $userflag['id'] . '" class="' . ((($count % 2) == 0) ? 'even' : 'odd') . ' libitem_menu">';
                    if ($this->gridview) {
                        echo '<td class="cel_song"><span style="font-weight: bold;">' . $item->f_link . '</span><br> ';
                        echo '<span style="margin-right: 10px;">';
                        if (AmpConfig::get('directplay')) {
                            echo Ajax::button('?page=stream&action=directplay&object_type=' . $userflag['type'] . '&object_id=' . $userflag['id'], 'play', T_('Play'), 'play_' . $userflag['type'] . '_' . $userflag['id']);
                            if (Stream_Playlist::check_autoplay_append()) {
                                echo Ajax::button('?page=stream&action=directplay&object_type=' . $userflag['type'] . '&object_id=' . $userflag['id'] . '&append=true', 'play_add', T_('Play last'), 'addplay_' . $userflag['type'] . '_' . $userflag['id']);
                            }
                        }
                        echo Ajax::button('?action=basket&type=' . $userflag['type'] . '&id=' . $userflag['id'], 'add', T_('Add to temporary playlist'), 'play_full_' . $userflag['id']);
                        echo '</span></td>';
                    }
                    echo '<td class=grid_cover>';
                    $thumb = ($this->gridview && UI::is_grid_view('album')) ? 1 : 12; // default to 150x150
                    $item->display_art($thumb, true);
                    echo '</td>';

                    if (!$this->gridview) {
                        echo '<td>' . $item->f_link . '</td>';
                    }

                    echo '<td class="optional">';
                    echo '<div style="white-space: normal;">' . $item->get_description() . '</div>';
                    echo '</div>';
                    if ($this->gridview) {
                        echo '<div style="float: right; opacity: 0.5;">' . T_('recommended by') . ' ' . $user->f_link . '</div>';
                        echo '</div><br />';
                    }
                    echo '</td></tr>';

                    $count++;
                }
            }
            echo '</table>';
            UI::show_box_bottom();
            echo '</div>';
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

        $this->maxitems = (int) ($data['catalogfav_max_items']);
        if ($this->maxitems < 1) {
            $this->maxitems = 5;
        }
        $this->gridview = ($data['catalogfav_gridview'] == '1');

        return true;
    }
}
