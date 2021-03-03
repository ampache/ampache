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

$web_path = AmpConfig::get('web_path');
$seconds  = $browse->duration;
$duration = floor($seconds / 3600) . gmdate(":i:s", $seconds % 3600);
$is_table = $browse->is_grid_view();
//mashup and grid view need different css
$cel_cover = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_flag  = ($is_table) ? "cel_userflag" : 'grid_userflag';
$cel_time  = ($is_table) ? "cel_time" : 'grid_time'; ?>
<?php if ($browse->is_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
    echo '<span class="item-duration">' . '| ' . T_('Duration') . ': ' . $duration . '</span>';
} ?>
<form method="post" id="reorder_playlist_<?php echo $playlist->id; ?>">
    <table id="reorder_playlist_table" class="tabledata <?php echo $browse->get_css_class() ?>" data-objecttype="media">
        <thead>
            <tr class="th-top">
                <th class="cel_play essential"></th>
                <?php if (Art::is_enabled()) { ?>
                <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art') ?></th>
                <?php
} ?>
                <th class="cel_title essential persist"><?php echo T_('Title'); ?></th>
                <th class="cel_add essential"></th>
                <th class="<?php echo $cel_time; ?> optional"><?php echo T_('Time'); ?></th>
                <?php if (User::is_registered()) { ?>
                    <?php if (AmpConfig::get('ratings')) { ?>
                        <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
                    <?php
} ?>
                    <?php if (AmpConfig::get('userflags')) { ?>
                <?php
        } ?>
                <th class="<?php echo $cel_flag; ?> optional"><?php echo T_('Fav.'); ?></th>
            <?php
    } ?>
                <th class="cel_action essential"><?php echo T_('Action'); ?></th>
                <th class="cel_drag essential"></th>
            </tr>
        </thead>
        <tbody id="sortableplaylist_<?php echo $playlist->id; ?>">
            <?php foreach ($object_ids as $object) {
        if (!is_array($object)) {
            $object = (array) $object;
        }
        $object_type = $object['object_type'];
        if (Core::is_library_item($object_type)) {
            $libitem = new $object_type($object['object_id']);
            $libitem->format();
            $playlist_track = $object['track']; ?>
        <tr class="<?php echo UI::flip_class() ?>" id="track_<?php echo $object['track_id'] ?>">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_playlist_media_row.inc.php'); ?>
        </tr>
        <?php
        }
    } ?>
        </tbody>
        <tfoot>
            <tr class="th-bottom">
                <th class="cel_play"><?php echo T_('Play'); ?></th>
                <?php if (Art::is_enabled()) { ?>
                <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art') ?></th>
                <?php
    } ?>
                <th class="cel_title"><?php echo T_('Title'); ?></th>
                <th class="cel_add"></th>
                <th class="<?php echo $cel_time; ?>"><?php echo T_('Time'); ?></th>
                <?php if (User::is_registered()) { ?>
                    <?php if (AmpConfig::get('ratings')) { ?>
                        <th class="cel_rating"><?php echo T_('Rating'); ?></th>
                    <?php
        } ?>
                    <?php if (AmpConfig::get('userflags')) { ?>
                        <th class="<?php echo $cel_flag; ?>"><?php echo T_('Fav.'); ?></th>
                    <?php
        } ?>
                <?php
    } ?>
                <th class="cel_action"><?php echo T_('Action'); ?></th>
                <th class="cel_drag"></th>
            </tr>
        </tfoot>
    </table>
</form>
<?php show_table_render($argument); ?>
<?php if ($browse->is_show_header()) {
        require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
        echo '<span class="item-duration">' . '| ' . T_('Duration') . ': ' . $duration . '</span>';
    } ?>
