<?php

/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

class AmpachePersonalFavorites extends AmpachePlugin implements PluginDisplayHomeInterface
{
    public string $name = 'Personal Favorites';

    public string $categories = 'home';

    public string $description = 'Personal favorites on homepage';

    public string $url = '';

    public string $version = '000003';

    public string $min_ampache = '370021';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private bool $display = false;

    private string $playlist = '';

    private string $smartlist = '';

    private int $order = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Personal favorites on homepage');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        if (!Preference::insert('personalfav_display', T_('Personal favorites on the homepage'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('personalfav_playlist', T_('Favorite Playlists'), '', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        if (!Preference::insert('personalfav_smartlist', T_('Favorite Smartlists'), '', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('personalfav_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('personalfav_display') &&
            Preference::delete('personalfav_playlist') &&
            Preference::delete('personalfav_smartlist') &&
            Preference::delete('personalfav_order')
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
            Preference::insert('personalfav_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name);
        }

        return true;
    }

    /**
     * display_home
     * This display the module in home page
     */
    public function display_home(): void
    {
        // display if you've enabled it
        if ($this->display) {
            $list_array = [];
            foreach (explode(',', $this->playlist) as $list_id) {
                $playlist = new Playlist((int)$list_id);
                if ($playlist->isNew() === false) {
                    $list_array[] = [$playlist, 'playlist'];
                }
            }

            foreach (explode(',', $this->smartlist) as $list_id) {
                $smartlist = new Search((int)$list_id);
                if ($smartlist->isNew() === false) {
                    $list_array[] = [$smartlist, 'search'];
                }
            }

            if ($list_array !== []) {
                $divString = ($this->order > 0)
                    ? '<div class="personalfav" style="order: ' . $this->order . '">'
                    : '<div class="personalfav">';
                echo $divString;
                Ui::show_box_top(T_('Favorite Lists'));
                echo '<table class="tabledata striped-rows';
                echo " gridview";
                echo '">';
                $count = 0;
                foreach ($list_array as $item) {
                    if ($item[0]->isNew() === false) {
                        echo '<tr id="playlist_' . $item[0]->id . '" class="libitem_menu">';
                        echo '<td style="height: 50px;">' . $item[0]->get_f_link() . '</td>';
                        echo '<td style="height: auto;">';
                        echo '<span style="margin-right: 10px;">';
                        if (AmpConfig::get('directplay')) {
                            echo Ajax::button('?page=stream&action=directplay&object_type=' . $item[1] . '&object_id=' . $item[0]->id, 'play_circle', T_('Play'), 'play_playlist_' . $item[0]->id);
                            if (Stream_Playlist::check_autoplay_next()) {
                                echo Ajax::button('?page=stream&action=directplay&object_type=' . $item[1] . '&object_id=' . $item[0]->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_playlist_' . $item[0]->id);
                            }

                            if (Stream_Playlist::check_autoplay_append()) {
                                echo Ajax::button(
                                    '?page=stream&action=directplay&object_type=' . $item[1] . '&object_id=' . $item[0]->id . '&append=true',
                                    'low_priority',
                                    T_('Play last'),
                                    'addplay_playlist_' . $item[0]->id
                                );
                            }
                        }

                        if ($item[0] instanceof Playlist) {
                            echo Ajax::button('?page=random&action=send_playlist&random_type=playlist&random_id=' . $item[0]->id, 'autorenew', T_('Random Play'), 'play_random_' . $item[0]->id);
                        }

                        if ($item[0] instanceof Search) {
                            echo Ajax::button('?page=random&action=send_playlist&random_type=search&random_id=' . $item[0]->id, 'autorenew', T_('Random Play'), 'play_random_' . $item[0]->id);
                        }

                        echo Ajax::button('?action=basket&type=' . $item[1] . '&id=' . $item[0]->id, 'new_window', T_('Add to Temporary Playlist'), 'play_full_' . $item[0]->id);
                        echo '</span></td>';
                        echo '<td class="optional">';
                        echo '</td></tr>';

                        $count++;
                    }
                }

                echo '</table>';
                Ui::show_box_bottom();
                echo '</div>';
            }
        }
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        $user->set_preferences();
        $data = $user->prefs;

        $this->display   = (array_key_exists('personalfav_display', $data) && $data['personalfav_display'] == '1');
        $this->playlist  = $data['personalfav_playlist'] ?? '';
        $this->smartlist = $data['personalfav_smartlist'] ?? '';
        $this->order     = (int)($data['personalfav_order'] ?? 0);

        return true;
    }
}
