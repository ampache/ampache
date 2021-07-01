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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */

$web_path      = AmpConfig::get('web_path');
$thcount       = 9;
$show_ratings  = User::is_registered() && (AmpConfig::get('ratings') || AmpConfig::get('userflags'));
$is_table      = $browse->is_grid_view();
$original_year = AmpConfig::get('use_original_year');
$year_sort     = ($original_year) ? "&sort=original_year" : "&sort=year";
$year_text     = T_('Year');
//mashup and grid view need different css
$cel_cover   = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_album   = ($is_table) ? "cel_album" : 'grid_album';
$cel_artist  = ($is_table) ? "cel_artist" : 'grid_artist';
$cel_tags    = ($is_table) ? "cel_tags" : 'grid_tags';
$cel_counter = ($is_table) ? "cel_counter" : 'grid_counter'; ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class() ?>" data-objecttype="album">
    <thead>
        <tr class="th-top">
        <div class="libitem_menu">
            <th class="cel_play essential"></th>
        <?php if (Art::is_enabled()) {
    ++$thcount; ?>
            <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art'); ?></th>
        <?php
} ?>
</div>
            <th class="<?php echo $cel_album; ?> essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Album'), 'album_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="<?php echo $cel_artist; ?> essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album_artist', T_('Album Artist'), 'album_sort_artist'); ?></th>
            <th class="cel_songs optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=song_count', T_('Songs'), 'album_sort_song_count'); ?></th>
            <th class="cel_year essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . $year_sort, $year_text, 'album_sort_year'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=total_count', T_('# Played'), 'album_sort_total_count'); ?></th>
            <?php
    } ?>
            <th class="<?php echo $cel_tags; ?> optional"><?php echo T_('Genres'); ?></th>
            <?php if ($show_ratings) {
        ++$thcount; ?>
                <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
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
            $show_playlist_add = Access::check('interface', 25);
            if ($directplay_limit > 0) {
                $show_playlist_add = ($libitem->song_count <= $directplay_limit);
                if ($show_direct_play) {
                    $show_direct_play = $show_playlist_add;
                }
            } ?>
        <tr id="album_<?php echo $libitem->id ?>" class="libitem_menu">
            <?php require Ui::find_template('show_album_row.inc.php'); ?>
        </tr>
        <?php
        }?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No Album found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <?php if (Art::is_enabled()) { ?>
                <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <?php
        } ?>
            <th class="<?php echo $cel_album; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Album'), 'album_sort_name_bottom'); ?></th>
            <th class="cel_add"></th>
            <th class="<?php echo $cel_artist; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album_artist', T_('Album Artist'), 'album_sort_artist_bottom'); ?></th>
            <th class="cel_songs"><?php echo T_('Songs'); ?></th>
            <th class="cel_year"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . $year_sort, $year_text, 'album_sort_year_bottom'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Played'); ?></th>
            <?php
        } ?>
            <th class="<?php echo $cel_tags; ?>"><?php echo T_('Genres'); ?></th>
            <?php if ($show_ratings) { ?>
                <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
                <?php
            } ?>
            <th class="cel_action"><?php echo T_('Actions'); ?></th>
        </tr>
    <tfoot>
</table>

<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
                require Ui::find_template('list_header.inc.php');
            } ?>
