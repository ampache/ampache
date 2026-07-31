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

// show_collection_items.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\User;

global $dic;
$libraryItemLoader = $dic->get(LibraryItemLoaderInterface::class);

/** @var Browse $browse */
/** @var Collection $collection */
/** @var array<int, array{object_type: LibraryItemEnum|string, object_id: int, track_id: int, track: int}>|null $object_ids */
/** @var bool|string|array<string, mixed> $argument */

$show_ratings = User::is_registered() && AmpConfig::get('ratings');
// Only a mixed collection reaches this template; a pinned one is handed to its own type's browse
$object_ids    = $object_ids ?? [];
$collection_id = $collection->getId();
$is_table      = !$browse->is_grid_view();
// Translations
$t_art    = T_('Art');
$t_title  = T_('Title');
$t_type   = T_('Type');
$t_parent = T_('Parent');
$t_time   = T_('Time');
$t_rating = T_('Rating');
$t_action = T_('Action');
// don't translate row text for every row
$t_play        = T_('Play');
$t_play_next   = T_('Play next');
$t_play_last   = T_('Play last');
$t_add_to_temp = T_('Add to Temporary Playlist');
$t_add_to_list = Ui::get_add_to_list_label(true);
$t_download    = T_('Download');
$t_delete      = T_('Delete');
$t_reorder     = T_('Reorder');

// A collaborator curates the contents, the same rule the API and the playlist item list use
$can_remove = $collection->has_collaborate();
$can_add    = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
$directplay = (bool) AmpConfig::get('directplay');
// The column is laid out wherever the actions would work, so toggling the option in the view menu shows and
// hides the checkboxes without every other column shifting sideways.
$can_multiselect  = $is_table && $object_ids !== [] && ($can_remove || $can_add || $directplay);
$show_multiselect = $can_multiselect && $browse->is_use_select();

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
        // `{track_ids}` addresses the `collection_map` rows directly, so one request covers a mixed selection
        'url' => Ajax::url('?page=collection&action=delete_track&collection_id=' . $collection_id . '&browse_id=' . $browse->getId() . '&track_id={track_ids}'),
        'icon' => 'playlist_remove',
        'text' => T_('Remove from collection'),
        'confirm' => T_('Remove {count} selected items from this collection?'),
    ];
}
if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
    <div<?php echo ($show_multiselect) ? ' data-multiselect-scope' : ''; ?>>
    <?php if ($show_multiselect) {
        require Ui::find_template('show_multiselect_actions.inc.php');
    } ?>
    <form method="post" id="reorder_collection_<?php echo $collection_id; ?>">
        <table id="reorder_collection_table" class="tabledata striped-rows" data-objecttype="collection_item" data-offset="<?php echo $browse->get_start(); ?>">
            <thead>
            <tr class="th-top">
                <?php if ($can_multiselect) { ?>
                <th class="cel_select essential persist"><?php if ($show_multiselect) { ?><input type="checkbox" class="multiselect-all" title="<?php echo T_('Select'); ?>" /><?php } ?></th>
                <?php } ?>
                <th class="cel_play essential"></th>
                <th class="cel_cover optional"><?php echo $t_art; ?></th>
                <th class="cel_title essential persist"><?php echo $t_title; ?></th>
                <th class="cel_type essential"><?php echo $t_type; ?></th>
                <th class="cel_artist optional"><?php echo $t_parent; ?></th>
                <th class="cel_add essential"></th>
                <th class="cel_time optional"><?php echo $t_time; ?></th>
                <?php if ($show_ratings) { ?>
                <th class="cel_ratings optional"><?php echo $t_rating; ?></th>
                <?php } ?>
                <th class="cel_action essential"><?php echo $t_action; ?></th>
                <?php if ($can_remove) { ?>
                <th class="cel_drag essential"></th>
                <?php } ?>
            </tr>
            </thead>
            <?php // `sortableplaylist_` is what `sortPlaylistRender()` looks for, so the drag handling is shared?>
            <tbody id="sortableplaylist_<?php echo $collection_id; ?>">
            <?php foreach ($object_ids as $object) {
                $libtype = (is_string($object['object_type']))
                    ? LibraryItemEnum::tryFrom($object['object_type'])
                    : $object['object_type'];
                $libitem = $libraryItemLoader->load($libtype, $object['object_id']);
                // A member whose object has since gone drops out rather than rendering an empty row
                if ($libitem === null) {
                    continue;
                }

                $object_type = $libtype?->value;
                // `tag` is the table; `genre` is what the API and the rest of the UI call it
                $type_label       = ($object_type === 'tag') ? 'genre' : (string) $object_type;
                $collection_track = (int) $object['track']; ?>
                <tr id="track_<?php echo $object['track_id']; ?>">
                    <?php require Ui::find_template('show_collection_item_row.inc.php'); ?>
                </tr>
                <?php
            } ?>
            </tbody>
            <tfoot>
            <tr class="th-bottom">
                <?php if ($can_multiselect) { ?>
                <th class="cel_select"></th>
                <?php } ?>
                <th class="cel_play"><?php echo $t_play; ?></th>
                <th class="cel_cover"><?php echo $t_art; ?></th>
                <th class="cel_title"><?php echo $t_title; ?></th>
                <th class="cel_type"><?php echo $t_type; ?></th>
                <th class="cel_artist"><?php echo $t_parent; ?></th>
                <th class="cel_add"></th>
                <th class="cel_time"><?php echo $t_time; ?></th>
                <?php if ($show_ratings) { ?>
                <th class="cel_ratings"><?php echo $t_rating; ?></th>
                <?php } ?>
                <th class="cel_action"><?php echo $t_action; ?></th>
                <?php if ($can_remove) { ?>
                <th class="cel_drag"></th>
                <?php } ?>
            </tr>
            </tfoot>
        </table>
    </form>
    </div>
<?php show_table_render(!is_array($argument) && (bool) $argument); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
