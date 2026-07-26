<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// show_playlist_medias.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\User;

global $dic;
$libraryItemLoader = $dic->get(LibraryItemLoaderInterface::class);

/** @var Browse $browse */
/** @var Playlist|Search $playlist */
/** @var array<int|string>|array<int, array{object_type: LibraryItemEnum|string, object_id: int, track_id: int, track: int}>|array<Song_Preview>|array<int, array{name?: string|null, id: int, track: int, raw: string, link?: string|null, track: int, oid?: int, vlid?: int}>|null $object_ids */
/** @var bool $argument */

$web_path = AmpConfig::get_web_path();

// playlists and searches come from the same 'playlist_media' browse but you can't reorder a search
$object_ids     = $object_ids ?? [];
$playlist_id    = $playlist->id ?: '';
$seconds        = $browse->duration;
$duration       = ($browse->duration === null) ? '' : floor($seconds / 3600) . gmdate(":i:s", $seconds % 3600);
$show_ratings   = User::is_registered() && (AmpConfig::get('ratings'));
$hide_genres    = AmpConfig::get('hide_genres');
$show_parent    = AmpConfig::get('show_playlist_media_parent', false);
$extended_links = AmpConfig::get('extended_playlist_links', false);
$is_table       = !$browse->is_grid_view();
// mashup and grid view need different css
$cel_cover = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_time  = ($is_table) ? "cel_time" : 'grid_time';
$css_class = ($is_table) ? '' : ' gridview';
$count     = 1;
// Translations
$t_duration = T_('Duration');
$t_art      = T_('Art');
$t_title    = T_('Title');
$t_artist   = T_('Artist');
$t_time     = T_('Time');
$t_rating   = T_('Rating');
$t_action   = T_('Action');
// don't translate media row text for every row
$t_play        = T_('Play');
$t_play_next   = T_('Play next');
$t_play_last   = T_('Play last');
$t_add_to_temp = T_('Add to Temporary Playlist');
$t_add_to_list = T_('Add to playlist');
$t_download    = T_('Download');
$t_delete      = T_('Delete');
$t_reorder     = T_('Reorder');
// Multi select. Only a real playlist can have tracks removed, a smartlist is rule driven, and the bar is
// pointless without rows or in grid view where there is no room for a checkbox column.
$can_remove       = ($playlist instanceof Playlist) && $playlist->has_collaborate();
$can_add          = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
$directplay       = (bool) AmpConfig::get('directplay');
$show_multiselect = $is_table && $object_ids !== [] && ($can_remove || $can_add || $directplay);

$multiselect_actions = [];
if ($directplay) {
    $multiselect_actions[] = [
        'action' => 'ajax',
        'url' => Ajax::url('?page=stream&action=directplay&object_type={type}&object_id={ids}'),
        'icon' => 'play_circle',
        'text' => $t_play,
    ];
    if (Stream_Playlist::check_autoplay_next()) {
        $multiselect_actions[] = [
            'action' => 'ajax',
            'url' => Ajax::url('?page=stream&action=directplay&object_type={type}&object_id={ids}&playnext=true'),
            'icon' => 'menu_open',
            'text' => $t_play_next,
        ];
    }
    if (Stream_Playlist::check_autoplay_append()) {
        $multiselect_actions[] = [
            'action' => 'ajax',
            'url' => Ajax::url('?page=stream&action=directplay&object_type={type}&object_id={ids}&append=true'),
            'icon' => 'low_priority',
            'text' => $t_play_last,
        ];
    }
}

$multiselect_actions[] = [
    'action' => 'ajax',
    'url' => Ajax::url('?action=basket&type={type}&id={ids}'),
    'icon' => 'new_window',
    'text' => $t_add_to_temp,
];
if ($can_add) {
    $multiselect_actions[] = [
        'action' => 'playlist',
        'url' => '',
        'icon' => 'playlist_add',
        'text' => $t_add_to_list,
    ];
}
if ($can_remove) {
    $multiselect_actions[] = [
        'action' => 'ajax',
        'url' => Ajax::url('?page=playlist&action=delete_track&playlist_id=' . $playlist->id . '&browse_id=' . $browse->getId() . '&track_id={track_ids}'),
        'icon' => 'playlist_remove',
        'text' => T_('Remove from playlist'),
        'confirm' => T_('Remove {count} selected items from this playlist?'),
    ];
}
if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
    echo '<span class="item-duration">' . '| ' . $t_duration . ': ' . $duration . '</span>';
} ?>
    <div<?php echo ($show_multiselect) ? ' data-multiselect-scope' : ''; ?>>
    <?php if ($show_multiselect) {
        require Ui::find_template('show_multiselect_actions.inc.php');
    } ?>
    <form method="post" id="reorder_playlist_<?php echo $playlist_id; ?>">
        <table id="reorder_playlist_table" class="tabledata striped-rows <?php echo $css_class; ?>" data-objecttype="media" data-offset="<?php echo $browse->get_start(); ?>">
            <thead>
            <tr class="th-top">
                <?php if ($show_multiselect) { ?>
                <th class="cel_select essential persist"><input type="checkbox" class="multiselect-all" title="<?php echo T_('Select all'); ?>" /></th>
                <?php } ?>
                <th class="cel_play essential"></th>
                <th class="<?php echo $cel_cover; ?> optional"><?php echo $t_art; ?></th>
                <th class="cel_title essential persist"><?php echo $t_title; ?></th>
                <?php if ($show_parent) { ?>
                <th class="cel_artist essential persist"><?php echo $t_artist; ?></th>
                <?php } ?>
                <th class="cel_add essential"></th>
                <th class="<?php echo $cel_time; ?> optional"><?php echo $t_time; ?></th>
                <?php if ($show_ratings) { ?>
                <th class="cel_ratings optional"><?php echo $t_rating; ?></th>
                <?php } ?>
                <th class="cel_action essential"><?php echo $t_action; ?></th>
                <th class="cel_drag essential"></th>
            </tr>
            </thead>
            <tbody id="sortableplaylist_<?php echo $playlist_id; ?>">
            <?php foreach ($object_ids as $object) {
                if (!is_array($object)) {
                    continue;
                }

                $libtype = null;
                $libitem = null;
                if (isset($object['object_type'])) {
                    $libtype = (is_string($object['object_type']))
                        ? LibraryItemEnum::tryFrom($object['object_type'])
                        : $object['object_type'];
                    $libitem = $libraryItemLoader->load(
                        $libtype,
                        $object['object_id'],
                    );
                }
                if ($libitem !== null) {
                    $object_type    = $libtype?->value;
                    $playlist_track = (int) ($object['track']); ?>
                    <tr id="track_<?php echo($object['track_id'] ?? 0); ?>">
                        <?php require Ui::find_template('show_playlist_media_row.inc.php'); ?>
                    </tr>
                    <?php
                }
                $count++;
            } ?>
            </tbody>
            <tfoot>
            <tr class="th-bottom">
                <?php if ($show_multiselect) { ?>
                <th class="cel_select"></th>
                <?php } ?>
                <th class="cel_play"><?php echo T_('Play'); ?></th>
                <th class="<?php echo $cel_cover; ?>"><?php echo $t_art; ?></th>
                <th class="cel_title"><?php echo $t_title; ?></th>
                <?php if ($show_parent) { ?>
                <th class="cel_artist essential persist"><?php echo $t_artist; ?></th>
                <?php } ?>
                <th class="cel_add"></th>
                <th class="<?php echo $cel_time; ?>"><?php echo $t_time; ?></th>
                <?php if ($show_ratings) { ?>
                    <th class="cel_ratings optional"><?php echo $t_rating; ?></th>
                <?php } ?>
                <th class="cel_action"><?php echo $t_action; ?></th>
                <th class="cel_drag"></th>
            </tr>
            </tfoot>
        </table>
    </form>
    </div>
<?php show_table_render($argument); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
    echo '<span class="item-duration">' . '| ' . $t_duration . ': ' . $duration . '</span>';
} ?>
