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

session_start();

$web_path = AmpConfig::get('web_path');
$thcount  = 8;
$is_table = $browse->is_grid_view();
//mashup and grid view need different css
$cel_cover   = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_album   = ($is_table) ? "cel_album" : 'grid_album';
$cel_artist  = ($is_table) ? "cel_artist" : 'grid_artist';
$cel_tags    = ($is_table) ? "cel_tags" : 'grid_tags';
$cel_flag    = ($is_table) ? "cel_userflag" : 'grid_userflag';
$cel_time    = ($is_table) ? "cel_time" : 'grid_time';
$cel_counter = ($is_table) ? "cel_counter" : 'grid_counter'; ?>
<?php if ($browse->is_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
} ?>
<table class="tabledata <?php echo $browse->get_css_class() ?>" data-objecttype="artist">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <?php if (Art::is_enabled()) {
    ++$thcount; ?>
                <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art'); ?></th>
            <?php
} ?>
            <th class="<?php echo $cel_artist; ?> essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=artist&sort=name', T_('Artist'), 'artist_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_songs optional"><?php echo T_('Songs');  ?></th>
            <th class="cel_albums optional"><?php echo T_('Albums'); ?></th>
            <th class="<?php echo $cel_time; ?> optional"><?php echo T_('Time'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Played'); ?></th>
            <?php
    } ?>
            <th class="<?php echo $cel_tags; ?> optional"><?php echo T_('Tags'); ?></th>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) {
        ++$thcount; ?>
                    <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
                <?php
    } ?>
                <?php if (AmpConfig::get('userflags')) {
        ++$thcount; ?>
                    <th class="<?php echo $cel_flag; ?> optional"><?php echo T_('Fav.'); ?></th>
                <?php
    } ?>
            <?php
    } ?>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Cache the ratings we are going to use
        if (AmpConfig::get('ratings')) {
            Rating::build_cache('artist', $object_ids);
        }
        if (AmpConfig::get('userflags')) {
            Userflag::build_cache('artist', $object_ids);
        }

        $show_direct_play_cfg = AmpConfig::get('directplay');
        $directplay_limit     = AmpConfig::get('direct_play_limit');

        /* Foreach through every artist that has been passed to us */
        foreach ($object_ids as $artist_id) {
            $libitem = new Artist($artist_id, $_SESSION['catalog']);
            $libitem->format(true, $limit_threshold);
            $show_direct_play  = $show_direct_play_cfg;
            $show_playlist_add = Access::check('interface', 25);
            if ($directplay_limit > 0) {
                $show_playlist_add = ($libitem->songs <= $directplay_limit);
                if ($show_direct_play) {
                    $show_direct_play = $show_playlist_add;
                }
            } ?>
        <tr id="artist_<?php echo $libitem->id ?>" class="<?php echo UI::flip_class() ?> libitem_menu">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_artist_row.inc.php'); ?>
        </tr>
        <?php
        } // end foreach ($artists as $artist)?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No Artist found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play essential"></th>
            <?php if (Art::is_enabled()) { ?>
                <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <?php
        } ?>
            <th class="<?php echo $cel_artist; ?> essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=artist&sort=name', T_('Artist'), 'artist_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_songs optional"><?php echo T_('Songs');  ?></th>
            <th class="cel_albums optional"><?php echo T_('Albums'); ?></th>
            <th class="<?php echo $cel_time; ?> essential"><?php echo T_('Time'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Played'); ?></th>
            <?php
        } ?>
            <th class="<?php echo $cel_tags; ?> optional"><?php echo T_('Tags'); ?></th>
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
            <th class="cel_action essential"> <?php echo T_('Action'); ?> </th>
        </tr>
    </tfoot>
</table>

<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
            require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
        } ?>
