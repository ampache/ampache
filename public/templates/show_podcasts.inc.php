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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;
use Ampache\Repository\PodcastRepositoryInterface;

/** @var Browse $browse */
/** @var list<int> $object_ids */
/** @var PodcastRepositoryInterface $podcastRepository */

$webPath      = AmpConfig::get_web_path();
$thcount      = 7;
$show_ratings = User::is_registered() && (AmpConfig::get('ratings'));
$is_table     = $browse->is_grid_view();
// translate once
$count_text  = T_('Played');
$rating_text = T_('Rating');
$action_text = T_('Actions');
//mashup and grid view need different css
$cel_cover   = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_counter = ($is_table) ? "cel_counter" : 'grid_counter'; ?>
<div id="information_actions">
    <ul>
        <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) { ?>
        <li>
            <a href="<?php echo $webPath; ?>/podcast.php?action=show_create">
                <?php echo Ui::get_material_symbol('add_circle', T_('Add')); ?>
                <?php echo T_('Subscribe to Podcast'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo $webPath; ?>/podcast.php?action=show_import_podcasts">
                <?php echo Ui::get_material_symbol('upload', T_('Import')); ?>
                <?php echo T_('Import'); ?>
            </a>
        </li>
        <?php } ?>
        <li>
            <a href="<?php echo $webPath; ?>/podcast.php?action=export_podcasts" target="_blank">
                <?php echo Ui::get_material_symbol('download', T_('Export')); ?>
                <?php echo T_('Export'); ?>
            </a>
        </li>
    </ul>
</div>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class(); ?>" data-objecttype="podcast">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <th class="cel_title essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Title'), 'podcast_sort_title'); ?></th>
            <th class="cel_siteurl"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=website', T_('Website'), 'podcast_sort_website'); ?></th>
            <th class="cel_episodes optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=episodes', T_('Episodes'), 'podcast_sort_episodes'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) {
                ++$thcount; ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=total_count' . $argument_param, $count_text, 'podcast_sort_total_count' . $browse->id); ?></th>
                <?php
            } ?>
            <?php if ($show_ratings) {
                ++$thcount; ?>
            <th class="cel_ratings optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=rating', $rating_text, 'podcast_sort_rating'); ?></th>
            <?php
            } ?>
            <th class="cel_action essential"><?php echo $action_text; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
                if (AmpConfig::get('ratings')) {
                    Rating::build_cache('podcast', $object_ids);
                    Userflag::build_cache('podcast', $object_ids);
                }

foreach ($object_ids as $podcastId) {
    $libitem = $podcastRepository->findById($podcastId);
    if ($libitem === null) {
        continue;
    }
    $libitem->format(); ?>
        <tr id="podcast_<?php echo $libitem->getId(); ?>">
            <?php require Ui::find_template('show_podcast_row.inc.php'); ?>
        </tr>
        <?php
} ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No podcast found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <th class="cel_title"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Title'), 'podcast_sort_title_bottom'); ?></th>
            <th class="cel_siteurl"><?php echo T_('Website'); ?></th>
            <th class="cel_episodes"><?php echo T_('Episodes'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
                <th class="<?php echo $cel_counter; ?> optional"><?php echo $count_text; ?></th>
            <?php } ?>
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
