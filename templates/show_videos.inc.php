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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var list<int> $object_ids */

$web_path = AmpConfig::get_web_path();

$show_ratings = User::is_registered() && (AmpConfig::get('ratings'));
$hide_genres  = AmpConfig::get('hide_genres');
$is_table     = !$browse->is_grid_view();
//mashup and grid view need different css
$cel_cover   = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_tags    = ($is_table) ? "cel_tags" : 'grid_tags';
$cel_counter = ($is_table) ? "cel_counter" : 'grid_counter';
$css_class   = ($is_table) ? '' : ' gridview';
if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $css_class; ?>" data-objecttype="video">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <th class="cel_title essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=title', T_('Title'), 'video_sort_title'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_release_date optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=release_date', T_('Release Date'), 'video_sort_release_date'); ?></th>
            <th class="cel_codec optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=codec', T_('Codec'), 'video_sort_codec'); ?></th>
            <th class="cel_resolution optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=resolution', T_('Resolution'), 'video_sort_resolution'); ?></th>
            <th class="cel_length optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=length', T_('Time'), 'video_sort_length'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=total_count', T_('Played'), 'video_sort_total_count' . $browse->id); ?></th>
            <?php } ?>
            <?php if (!$hide_genres) { ?>
            <th class="<?php echo $cel_tags; ?> optional"><?php echo T_('Genres'); ?></th>
            <?php } ?>
            <?php if ($show_ratings) { ?>
            <th class="cel_ratings optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=rating', T_('Rating'), 'video_sort_rating'); ?></th>
            <?php } ?>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($object_ids as $video_id) {
            $libitem = new Video($video_id);
            if ($libitem->isNew()) {
                continue;
            } ?>
        <tr id="video_<?php echo $libitem->id; ?>">
            <?php require Ui::find_template('show_video_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="42"><span class="nodata"><?php echo T_('No video found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <th class="cel_title"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=title', T_('Title'), 'video_sort_title'); ?></th>
            <th class="cel_add"></th>
            <th class="cel_release_date"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=release_date', T_('Release Date'), 'video_sort_release_date'); ?></th>
            <th class="cel_codec"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=codec', T_('Codec'), 'video_sort_codec'); ?></th>
            <th class="cel_resolution"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=resolution', T_('Resolution'), 'video_sort_resolution'); ?></th>
            <th class="cel_length"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=video&sort=length', T_('Time'), 'video_sort_length'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('Played'); ?></th>
            <?php } ?>
            <?php if (!$hide_genres) { ?>
            <th class="<?php echo $cel_tags; ?>"><?php echo T_('Genres'); ?></th>
            <?php } ?>
            <?php if ($show_ratings) { ?>
            <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
            <?php } ?>
            <th class="cel_action"><?php echo T_('Action'); ?></th>
        </tr>
    </tfoot>
</table>
<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
