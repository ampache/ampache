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
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/** @var Ampache\Repository\Model\Podcast $podcast */
/** @var array $object_ids */
/** @var string $object_type */
/** @var User $current_user */

$access75 = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER);
$access50 = ($access75 || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER));
$browse   = new Browse();
$browse->set_type($object_type);
$browse->set_use_filters(false);
$web_path = AmpConfig::get_web_path();
Ui::show_box_top((string)$podcast->get_fullname(), 'info-box'); ?>
<div class="item_right_info">
    <?php
    $thumb = Ui::is_grid_view('podcast') ? 32 : 11;
Art::display('podcast', $podcast->getId(), (string)$podcast->get_fullname(), $thumb); ?>
</div>
<?php if ($podcast->get_description()) { ?>
<div id="item_summary">
    <?php echo $podcast->get_description(); ?>
</div>
<?php } ?>
<?php if (User::is_registered() && AmpConfig::get('ratings')) { ?>
    <span id="rating_<?php echo (int) ($podcast->getId()); ?>_podcast">
        <?php echo Rating::show($podcast->getId(), 'podcast'); ?>
    </span>
    <span id="userflag_<?php echo $podcast->getId(); ?>_podcast">
        <?php echo Userflag::show($podcast->getId(), 'podcast'); ?>
    </span>
<?php } ?>
<div id="information_actions">
    <h3><?php echo T_('Actions'); ?>:</h3>
    <ul>
        <?php if (AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=podcast&object_id=' . $podcast->getId(), 'play_circle', T_('Play All'), 'directplay_full_' . $podcast->getId()); ?>
        </li>
        <?php } ?>
        <?php if (Stream_Playlist::check_autoplay_next()) { ?>
            <li>
                <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=podcast&object_id=' . $podcast->getId() . '&playnext=true', 'menu_open', T_('Play All Next'), 'addnext_podcast_' . $podcast->getId()); ?>
            </li>
            <?php } ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=podcast&object_id=' . $podcast->getId() . '&append=true', 'low_priority', T_('Play All Last'), 'addplay_podcast_' . $podcast->getId()); ?>
        </li>
        <?php } ?>
        <?php if ($access50) { ?>
        <?php if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../vendor/szymach/c-pchart/src/Chart/')) { ?>
            <li>
                <a href="<?php echo $web_path; ?>/stats.php?action=graph&object_type=podcast&object_id=<?php echo $podcast->getId(); ?>">
                    <?php echo Ui::get_material_symbol('bar_chart', T_('Graphs')); ?>
                    <?php echo T_('Graphs'); ?>
                </a>
            </li>
        <?php } ?>
    <?php if (AmpConfig::get('use_rss')) { ?>
        <li>
            <?php echo Ui::getRssLink(
                RssFeedTypeEnum::LIBRARY_ITEM,
                $current_user,
                T_('RSS Feed'),
                ['object_type' => 'podcast', 'object_id' => (string)$podcast->getId()]
            ); ?>
        </li>
        <?php } ?>
        <li>
            <a href="<?php echo $podcast->getWebsite(); ?>" target="_blank">
                <?php echo Ui::get_material_symbol('link', T_('Website')); ?>
                <?php echo T_('Website'); ?>
            </a>
        </li>
        <li>
            <a id="<?php echo 'edit_podcast_' . $podcast->getId(); ?>" onclick="showEditDialog('podcast_row', '<?php echo $podcast->getId(); ?>', '<?php echo 'edit_podcast_' . $podcast->getId(); ?>', '<?php echo addslashes(T_('Podcast Edit')); ?>', '')">
                <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                <?php echo T_('Edit Podcast'); ?>
            </a>
        </li>
        <li>
            <?php echo Ajax::button_with_text('?page=podcast&action=syncPodcast&podcast_id=' . $podcast->getId(), 'sync', T_('Sync'), 'sync_podcast_' . $podcast->getId()); ?>
        </li>
        <?php } ?>
        <?php if ($access75) { ?>
        <li>
            <a id="<?php echo 'delete_podcast_' . $podcast->getId(); ?>" href="<?php echo $web_path; ?>/podcast.php?action=delete&podcast_id=<?php echo $podcast->getId(); ?>">
                <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
                <?php echo T_('Delete'); ?>
            </a>
        </li>
        <?php } ?>
    </ul>
</div>
<?php Ui::show_box_bottom(); ?>
<div class="tabs_wrapper">
    <div id="tabs_container">
        <ul id="tabs">
            <li class="tab_active"><a href="#episodes"><?php echo T_('Episodes'); ?></a></li>
            <!-- Needed to avoid the 'only one' bug -->
            <li></li>
        </ul>
    </div>
    <div id="tabs_content">
        <div id="episodes" class="tab_content" style="display: block;">
<?php $browse->show_objects($object_ids, true);
$browse->store(); ?>
        </div>
    </div>
</div>
