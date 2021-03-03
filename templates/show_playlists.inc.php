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
$is_table = $browse->is_grid_view();
//mashup and grid view need different css
$cel_cover = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_flag  = ($is_table) ? "cel_userflag" : 'grid_userflag'; ?>
<?php if ($browse->is_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
} ?>
<table class="tabledata <?php echo $browse->get_css_class() ?>" data-objecttype="playlist">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <?php if (AmpConfig::get('playlist_art')) { ?>
            <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art') ?></th>
            <?php
} ?>
            <th class="cel_playlist essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=name', T_('Playlist Name'), 'playlist_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_last_update optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=last_update', T_('Last Update'), 'playlist_sort_last_update'); ?></th>
            <th class="cel_type optional"><?php echo T_('Type'); ?></th>
            <th class="cel_medias optional"><?php /* HINT: Number of items in a playlist */ echo T_('# Items'); ?></th>
            <th class="cel_owner optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=user', T_('Owner'), 'playlist_sort_owner'); ?></th>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) { ?>
                    <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
                <?php
        } ?>
                <?php if (AmpConfig::get('userflags')) { ?>
                    <th class="<?php echo $cel_flag; ?> optional"><?php echo T_('Fav.'); ?></th>
                <?php
        } ?>
            <?php
    } ?>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $playlist_id) {
            $libitem = new Playlist($playlist_id);
            $libitem->format();

            // Don't show empty playlist if not admin or the owner
            if (Access::check('interface', 100) || $libitem->get_user_owner() == Core::get_global('user')->id || $libitem->get_media_count() > 0) { ?>
        <tr class="<?php echo UI::flip_class(); ?>" id="playlist_row_<?php echo $libitem->id; ?>">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_playlist_row.inc.php'); ?>
        </tr>
        <?php
            }
        } // end foreach ($playlists as $playlist)?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="7"><span class="nodata"><?php echo T_('No playlist found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play essential"></th>
            <?php if (AmpConfig::get('playlist_art')) { ?>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art') ?></th>
            <?php
        } ?>
            <th class="cel_playlist essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=name', T_('Playlist Name'), 'playlist_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_last_update"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=last_update', T_('Last Update'), 'playlist_sort_last_update_bottom'); ?></th>
            <th class="cel_type optional"><?php echo T_('Type'); ?></th>
            <th class="cel_medias optional"><?php /* HINT: Number of items in a playlist */ echo T_('# Items'); ?></th>
            <th class="cel_owner optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=user', T_('Owner'), 'playlist_sort_owner_bottom'); ?></th>
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
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </tfoot>
</table>
<?php if ($browse->is_show_header()) {
            require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
        } ?>
