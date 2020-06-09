<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$web_path = AmpConfig::get('web_path');
$thcount  = 8; ?>
<?php if ($browse->is_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
} ?>
<table class="tabledata <?php echo $browse->get_css_class() ?>" data-objecttype="album">
    <thead>
        <tr class="th-top">
        <div class="even libitem_menu">
            <th class="cel_play essential"></th>
        <?php if (Art::is_enabled()) {
    ++$thcount; ?>
            <th class="cel_cover optional"><?php echo T_('Art'); ?></th>
        <?php
} ?>
</div>
            <th class="cel_album essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Album'), 'album_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_artist essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=generic_artist', T_('Artist'), 'album_sort_artist'); ?></th>
            <th class="cel_songs optional"><?php echo T_('Songs'); ?></th>
            <th class="cel_year essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=year', T_('Year'), 'album_sort_year'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="cel_counter optional"><?php echo T_('# Played'); ?></th>
            <?php
    } ?>
            <th class="cel_tags optional"><?php echo T_('Tags'); ?></th>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) {
        ++$thcount; ?>
                    <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
                <?php
    } ?>
                <?php if (AmpConfig::get('userflags')) {
        ++$thcount; ?>
                    <th class="cel_userflag optional"><?php echo T_('Fav.'); ?></th>
                <?php
    } ?>
            <?php
    } ?>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (AmpConfig::get('ratings')) {
            Rating::build_cache('album', $object_ids);
        }
        if (AmpConfig::get('userflags')) {
            Userflag::build_cache('album', $object_ids);
        }

        $show_direct_play_cfg = AmpConfig::get('directplay');
        $directplay_limit     = AmpConfig::get('direct_play_limit');

        /* Foreach through the albums */
        foreach ($object_ids as $album_id) {
            $libitem = new Album($album_id);
            $libitem->format(true, $limit_threshold);
            $show_direct_play  = $show_direct_play_cfg;
            $show_playlist_add = Access::check('interface', '25');
            if ($directplay_limit > 0) {
                $show_playlist_add = ($libitem->song_count <= $directplay_limit);
                if ($show_direct_play) {
                    $show_direct_play = $show_playlist_add;
                }
            } ?>
        <tr id="album_<?php echo $libitem->id ?>" class="<?php echo UI::flip_class() ?> libitem_menu">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_album_row.inc.php'); ?>
        </tr>
        <?php
        }?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No Album found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <?php if (Art::is_enabled()) { ?>
                <th class="cel_cover"><?php echo T_('Art'); ?></th>
            <?php
        } ?>
            <th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Album'), 'album_sort_name_bottom'); ?></th>
            <th class="cel_add"></th>
            <th class="cel_artist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=generic_artist', T_('Artist'), 'album_sort_artist_bottom'); ?></th>
            <th class="cel_songs"><?php echo T_('Songs'); ?></th>
            <th class="cel_year"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=year', T_('Year'), 'album_sort_year_bottom'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="cel_counter optional"><?php echo T_('# Played'); ?></th>
            <?php
        } ?>
            <th class="cel_tags"><?php echo T_('Tags'); ?></th>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) { ?>
                    <th class="cel_rating"><?php echo T_('Rating'); ?></th>
                <?php
            } ?>
                <?php if (AmpConfig::get('userflags')) { ?>
                    <th class="cel_userflag"><?php echo T_('Fav.'); ?></th>
                <?php
            } ?>
            <?php
        } ?>
            <th class="cel_action"><?php echo T_('Actions'); ?></th>
        </tr>
    <tfoot>
</table>

<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
            require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
        } ?>
