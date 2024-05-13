<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

class AmpacheCatalogFavorites implements AmpachePluginInterface
{
    public string $name        = 'Catalog Favorites';
    public string $categories  = 'home';
    public string $description = 'Catalog favorites on homepage';
    public string $url         = '';
    public string $version     = '000002';
    public string $min_ampache = '370021';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $maxitems;
    private $gridview;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Catalog favorites on homepage');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('catalogfav_max_items', T_('Catalog favorites max items'), 5, AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }
        if (!Preference::insert('catalogfav_gridview', T_('Catalog favorites grid view display'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name)) {
            return false;
        }

        return true;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('catalogfav_max_items') &&
            Preference::delete('catalogfav_gridview')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version == 0) {
            return false;
        }
        if ($from_version < (int)$this->version) {
            Preference::insert('catalogfav_gridview', T_('Catalog favorites grid view display'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name);
        }

        return true;
    }

    /**
     * display_home
     * This display the module in home page
     */
    public function display_home(): void
    {
        if (AmpConfig::get('ratings')) {
            $userflags = Userflag::get_latest('song', null, $this->maxitems);
            $count     = 0;
            echo '<div class="home_plugin">';
            Ui::show_box_top(T_('Highlight'));
            echo '<table class="tabledata striped-rows';
            if (!$this->gridview) {
                echo " disablegv";
            }
            echo '">';
            foreach ($userflags as $userflag) {
                $item = new Song($userflag);
                if ($item->isNew() === false) {
                    echo '<tr id="song_' . $userflag . '" class="libitem_menu">';
                    if ($this->gridview) {
                        echo '<td class="cel_song"><span style="font-weight: bold;">' . $item->get_f_link() . '</span><br> ';
                        echo '<span style="margin-right: 10px;">';
                        if (AmpConfig::get('directplay')) {
                            echo Ajax::button(
                                '?page=stream&action=directplay&object_type=song&object_id=' . $userflag,
                                'play_circle',
                                T_('Play'),
                                'play_song_' . $userflag
                            );
                            if (Stream_Playlist::check_autoplay_next()) {
                                echo Ajax::button(
                                    '?page=stream&action=directplay&object_type=song&object_id=' . $userflag . '&playnext=true',
                                    'menu_open',
                                    T_('Play next'),
                                    'nextplay_song_' . $userflag
                                );
                            }
                            if (Stream_Playlist::check_autoplay_append()) {
                                echo Ajax::button(
                                    '?page=stream&action=directplay&object_type=song&object_id=' . $userflag . '&append=true',
                                    'low_priority',
                                    T_('Play last'),
                                    'addplay_song_' . $userflag
                                );
                            }
                        }
                        echo Ajax::button('?action=basket&type=song&id=' . $userflag, 'new_window', T_('Add to Temporary Playlist'), 'play_full_' . $userflag);
                        echo '</span></td>';
                    }
                    echo '<td class=grid_cover>';
                    $thumb = ($this->gridview && UI::is_grid_view('album')) ? 1 : 12; // default to 150x150
                    $item->display_art($thumb, true);
                    echo '</td>';

                    if (!$this->gridview) {
                        echo '<td>' . $item->get_f_link() . '</td>';
                    }

                    echo '<td class="optional">';
                    echo '<div style="white-space: normal;">' . $item->get_description() . '</div>';
                    echo '</div>';
                    echo '</td></tr>';

                    $count++;
                }
            }
            echo '</table>';
            Ui::show_box_bottom();
            echo '</div>';
        }
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     * @param User $user
     */
    public function load($user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->maxitems = (int)($data['catalogfav_max_items']);
        if ($this->maxitems < 1) {
            $this->maxitems = 5;
        }
        $this->gridview = ($data['catalogfav_gridview'] == '1');

        return true;
    }
}
