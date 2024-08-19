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
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var list<int> $object_ids */

$thcount      = 6;
$show_ratings = User::is_registered() && (AmpConfig::get('ratings'));
$is_mashup    = $browse->is_mashup();
$is_table     = $browse->is_grid_view();
// translate once
$count_text  = T_('Played');
$rating_text = T_('Rating');
$action_text = T_('Actions');
//mashup and grid view need different css
$cel_cover   = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_time    = ($is_table) ? "cel_time" : 'grid_time';
$cel_counter = ($is_table) ? "cel_counter" : 'grid_counter'; ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class(); ?>" data-objecttype="podcast_episode">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="cel_title essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Title'), 'podcast_episode_sort_title'); ?></th>
            <th class="cel_add essential"></th>
            <?php if ($is_mashup) {
                ++$thcount; ?>
            <th class="cel_podcast"><?php echo T_('Podcast'); ?></th>
                <?php
            } ?>
            <th class="<?php echo $cel_time; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=time', T_('Time'), 'podcast_episode_sort_time'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=total_count' . $argument_param, $count_text, 'podcast_episode_sort_total_count' . $browse->id); ?></th>
            <?php } ?>
            <th class="cel_pubdate optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=pubdate', T_('Publication Date'), 'podcast_episode_sort_pubdate'); ?></th>
            <th class="cel_state optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=state', T_('Status'), 'podcast_episode_sort_state'); ?></th>
            <?php if ($show_ratings) {
                ++$thcount; ?>
                <th class="cel_ratings optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=rating', $rating_text, 'podcast_episode_sort_rating'); ?></th>
            <?php
            } ?>
            <th class="cel_action essential"><?php echo $action_text; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if (AmpConfig::get('ratings')) {
            Rating::build_cache('podcast_episode', $object_ids);
            Userflag::build_cache('podcast_episode', $object_ids);
        }
foreach ($object_ids as $episode_id) {
    $libitem = new Podcast_Episode($episode_id);
    if ($libitem->isNew()) {
        continue;
    }
    $libitem->format(); ?>
        <tr id="podcast_episode_<?php echo $libitem->id; ?>">
            <?php require Ui::find_template('show_podcast_episode_row.inc.php'); ?>
        </tr>
        <?php } ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No podcast episode found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <th class="cel_title"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Title'), 'podcast_episode_sort_title_bottom'); ?></th>
            <th class="cel_add"></th>
            <?php if ($is_mashup) { ?>
            <th class="cel_podcast"><?php echo T_('Podcast'); ?></th>
            <?php } ?>
            <th class="<?php echo $cel_time; ?>"><?php echo T_('Time'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo $count_text; ?></th>
            <?php } ?>
            <th class="cel_pubdate"><?php echo T_('Publication Date'); ?></th>
            <th class="cel_state"><?php echo T_('Status'); ?></th>
            <?php if ($show_ratings) { ?>
            <th class="cel_ratings optional"><?php echo $rating_text; ?></th>
            <?php } ?>
            <th class="cel_action"><?php echo $action_text; ?></th>
        </tr>
    <tfoot>
</table>
<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
