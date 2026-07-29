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
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\User;

global $dic;
$libraryItemLoader = $dic->get(LibraryItemLoaderInterface::class);

/** @var Browse $browse */
/** @var array<int, array{object_type: LibraryItemEnum|string, object_id: int, track_id: int, track: int}>|null $object_ids */
/** @var bool|string|array<string, mixed> $argument */

$show_ratings = User::is_registered() && AmpConfig::get('ratings');
// Only a mixed collection reaches this template; a pinned one is handed to its own type's browse
$object_ids = $object_ids ?? [];
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
$t_add_to_list = T_('Add to playlist');
$t_download    = T_('Download');

if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
    <table class="tabledata striped-rows" data-objecttype="collection_item" data-offset="<?php echo $browse->get_start(); ?>">
        <thead>
        <tr class="th-top">
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
        </tr>
        </thead>
        <tbody>
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
            <tr id="collection_item_<?php echo $object['track_id']; ?>">
                <?php require Ui::find_template('show_collection_item_row.inc.php'); ?>
            </tr>
            <?php
        } ?>
        </tbody>
        <tfoot>
        <tr class="th-bottom">
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
        </tr>
        </tfoot>
    </table>
<?php show_table_render(!is_array($argument) && (bool) $argument); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
