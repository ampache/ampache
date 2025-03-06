<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

class AmpacheCatalogFavorites extends AmpachePlugin implements PluginDisplayHomeInterface
{
    public string $name        = 'Catalog Favorites';

    public string $categories  = 'home';

    public string $description = 'Catalog favorites on homepage';

    public string $url         = '';

    public string $version     = '000004';

    public string $min_ampache = '370021';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $maxitems;

    private $gridview;

    private int $order = 0;

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

        if (!Preference::insert('catalogfav_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('catalogfav_compact', T_('Catalog favorites media row display'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name);
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('catalogfav_max_items') &&
            Preference::delete('catalogfav_gridview') &&
            Preference::delete('catalogfav_order') &&
            Preference::delete('catalogfav_compact')
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

        if ($from_version < 3) {
            Preference::insert('catalogfav_gridview', T_('Catalog favorites grid view display'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name);
            Preference::insert('catalogfav_order', T_('Plugin CSS order'), '0', AccessLevelEnum::USER->value, 'integer', 'plugins', $this->name);
        }

        if ($from_version < (int)$this->version) {
            Preference::insert('catalogfav_compact', T_('Catalog favorites media row display'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name);
        }

        return true;
    }

    /**
     * display_home
     * This display the module in home page
     */
    public function display_home(): void
    {
        $userflags = Userflag::get_latest('song', null, $this->maxitems);
        if (
            AmpConfig::get('ratings') &&
            $userflags !== []
        ) {
            $divString = ($this->order > 0)
                ? '<div class="catalogfav" style="order: ' . $this->order . '">'
                : '<div class="catalogfav">';
            echo $divString;
            Ui::show_box_top(T_('Highlight'));
            if ($this->compact ?? true) {
                $showAlbum    = AmpConfig::get('album_group');
                $show_ratings = User::is_registered() && (AmpConfig::get('ratings')); ?>
                <table class="tabledata striped-rows">
                <thead>
                <tr class="th-top">
                    <th class="cel_play"></th>
                    <th class="cel_cover optional"><?php echo T_('Art'); ?></th>
                    <th class="cel_song"><?php echo T_('Song'); ?></th>
                    <th class="cel_add"></th>
                    <th class="cel_artist"><?php echo T_('Song Artist'); ?></th>
                    <th class="cel_album"><?php echo T_('Album'); ?></th>
                    <th class="cel_year"><?php echo T_('Year'); ?></th>
                    <?php if ($show_ratings) { ?>
                        <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
                    <?php } ?>
                    <th class="cel_action essential"><?php echo T_('Action'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php $count = 0;
                foreach ($userflags as $userflag) {
                    $item = new Song($userflag);
                    if ($item->isNew() === false) {
                        $item->format(); ?>
                        <tr>
                            <td class="cel_play">
                                <span class="cel_play_content">&nbsp;</span>
                                <div class="cel_play_hover">
                                    <?php if (AmpConfig::get('directplay')) { ?>
                                        <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $item->id, 'play_circle', T_('Play'), 'play_song_' . $count . '_' . $item->id); ?>
                                        <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                                            <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $item->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_song_' . $count . '_' . $item->id); ?>
                                        <?php } ?>
                                        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                                            <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $item->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_song_' . $count . '_' . $item->id); ?>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                            </td>
                            <td class="cel_cover">
                                <div style="max-width: 80px;">
                                    <?php $item->display_art(3); ?>
                                </div>
                            </td>
                            <td class="cel_song"><?php echo $item->get_f_link(); ?></td>
                            <td class="cel_add">
                                <span class="cel_item_add">
                                    <?php echo Ajax::button('?action=basket&type=song&id=' . $item->id, 'new_window', T_('Add to Temporary Playlist'), 'add_' . $count . '_' . $item->id); ?>
                                    <a id="<?php echo 'add_playlist_' . $count . '_' . $item->id; ?>" onclick="showPlaylistDialog(event, 'song', '<?php echo $item->id; ?>')">
                                        <?php echo Ui::get_material_symbol('playlist_add', T_('Add to playlist')); ?>
                                    </a>
                                </span>
                            </td>
                            <td class="cel_artist"><?php echo $item->get_f_parent_link(); ?></td>
                            <td class="cel_album"><?php echo ($showAlbum) ? $item->get_f_album_link() : $item->get_f_album_disk_link(); ?></td>
                            <td class="cel_year"><?php echo $item->year; ?></td>
                        <?php if ($show_ratings) { ?>
                            <td class="cel_ratings">
                                <?php if (AmpConfig::get('ratings')) { ?>
                                    <div class="rating">
                <span class="cel_rating" id="rating_<?php echo $item->getId(); ?>_song">
                    <?php echo Rating::show($item->getId(), 'song'); ?>
                </span>
                                        <span class="cel_userflag" id="userflag_<?php echo $item->getId(); ?>_song">
                    <?php echo Userflag::show($item->getId(), 'song'); ?>
                </span>
                                    </div>
                                <?php } ?>
                            </td>
                        <?php } ?>
                        <td class="cel_action">
                        <?php if (AmpConfig::get('download')) { ?>
                            <a class="nohtml" href="<?php echo AmpConfig::get_web_path(); ?>/stream.php?action=download&song_id=<?php echo $item->getId(); ?>">
                                <?php echo Ui::get_material_symbol('download', T_('Download')); ?>
                            </a>
                            <?php
                        }
                        if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) && AmpConfig::get('share')) {
                            echo Share::display_ui('song', $item->getId(), false);
                        } ?>
                            </td>
                        </tr>
                        <?php
                        ++$count;
                    }
                } ?>
                </tbody>
                <tfoot>
                <tr class="th-bottom">
                    <th class="cel_play"></th>
                    <td class="cel_cover">
                    <th class="cel_song"></th>
                    <th class="cel_add"></th>
                    <th class="cel_artist"></th>
                    <th class="cel_album"></th>
                    <th class="cel_year"></th>
                    <?php if ($show_ratings) { ?>
                        <th class="cel_ratings optional"></th>
                    <?php } ?>
                    <th class="cel_action"></th>
                </tr>
                </tfoot>
                </table>
            <?php } else {
                echo '<table class="tabledata striped-rows';
                if ($this->gridview) {
                    echo " gridview";
                }

                echo '">';
                foreach ($userflags as $userflag) {
                    $item = new Song($userflag);
                    if ($item->isNew() === false) {
                        echo '<tr id="song_' . $userflag . '" class="libitem_menu">';
                        if (!$this->gridview) {
                            echo '<td class="grid_song"><span style="font-weight: bold;">' . $item->get_f_link() . '</span><br> ';
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
                        $thumb = ($this->gridview) ? 12 : 1; // default to 100x100
                        $item->display_art($thumb, true);
                        echo '</td>';

                        if ($this->gridview) {
                            echo '<td>' . $item->get_f_link() . '</td>';
                        }

                        echo '<td class="optional">';
                        echo '<div style="white-space: normal;">' . $item->get_description() . '</div>';
                        echo '</div>';
                        echo '</td></tr>';
                    }
                }

                echo '</table>';
            }
            Ui::show_box_bottom();
            echo '</div>';
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

        $this->maxitems = (int)($data['catalogfav_max_items']);
        if ($this->maxitems < 1) {
            $this->maxitems = 5;
        }

        $this->gridview = ($data['catalogfav_gridview'] == '1');
        $this->order    = (int)($data['catalogfav_order'] ?? 0);

        return true;
    }
}
