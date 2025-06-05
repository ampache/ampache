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
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Podcast_Episode $libitem */
/** @var Ampache\Repository\Model\Browse $browse */
/** @var bool $is_mashup */
/** @var bool $is_table */
/** @var bool $show_ratings */
/** @var string $cel_cover */
/** @var string $cel_time */
/** @var string $cel_counter */

$web_path = AmpConfig::get_web_path(); ?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
        if (AmpConfig::get('directplay') && !empty($libitem->file)) {
            echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $libitem->id, 'play_circle', T_('Play'), 'play_podcast_episode_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $libitem->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_podcast_episode_' . $libitem->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $libitem->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_podcast_episode_' . $libitem->id);
            }
        } ?>
    </div>
</td>
<?php
if ($is_mashup) {
    $name = scrub_out((string)$libitem->get_fullname()); ?>
    <td class="<?php echo $cel_cover; ?>">
        <?php $size = ($browse->is_grid_view())
            ? ['width' => 150, 'height' => 150]
            : ['width' => 100, 'height' => 100];
    Art::display('podcast_episode', $libitem->id, $name, $size, $web_path . '/podcast_episode.php?action=show&podcast_episode=' . $libitem->id); ?>
    </td>
<?php
} ?>
<td class="cel_title"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_add">
    <span class="cel_item_add">
<?php
    echo Ajax::button('?action=basket&type=podcast_episode&id=' . $libitem->id, 'new_window', T_('Add to Temporary Playlist'), 'add_' . $libitem->id);
if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
        <a id="<?php echo 'add_to_playlist_' . $libitem->id; ?>" onclick="showPlaylistDialog(event, 'podcast_episode', '<?php echo $libitem->id; ?>')">
            <?php echo Ui::get_material_symbol('playlist_add', T_('Add to playlist')); ?>
        </a>
    <?php } ?>
    </span>
</td>
<?php if (!$is_table) { ?>
<td class="cel_podcast"><?php echo $libitem->getPodcastLink(); ?></td>
<?php } ?>
<td class="<?php echo $cel_time; ?>"><?php echo $libitem->get_f_time(); ?></td>
<?php if (AmpConfig::get('show_played_times')) { ?>
    <td class="<?php echo $cel_counter; ?> optional"><?php echo $libitem->total_count; ?></td>
    <?php } ?>
<td class="cel_pubdate optional"><?php echo $libitem->getPubDate()->format(DATE_ATOM); ?></td>
<td class="cel_state optional"><?php echo $libitem->getState()->toDescription(); ?></td>
<?php
if ($show_ratings) { ?>
        <td class="cel_ratings">
            <?php if (AmpConfig::get('ratings')) { ?>
                <div class="rating">
                    <span class="cel_rating" id="rating_<?php echo $libitem->id; ?>_podcast_episode">
                        <?php echo Rating::show($libitem->id, 'podcast_episode'); ?>
                    </span>
                    <span class="cel_userflag" id="userflag_<?php echo $libitem->id; ?>_podcast_episode">
                        <?php echo Userflag::show($libitem->id, 'podcast_episode'); ?>
                    </span>
                </div>
            <?php } ?>
        </td>
    <?php } ?>
<td class="cel_action">
    <?php if (Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD) && !empty($libitem->file)) { ?>
            <a class="nohtml" href="<?php echo $web_path; ?>/stream.php?action=download&podcast_episode_id=<?php echo $libitem->id; ?>"><?php echo Ui::get_material_symbol('download', T_('Download')); ?></a>
        <?php } ?>
<?php
if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
    <span id="button_sync_<?php echo $libitem->id; ?>">
        <?php echo Ajax::button('?page=podcast&action=syncPodcastEpisode&podcast_episode_id=' . $libitem->id, 'sync', T_('Sync'), 'sync_podcast_episode_' . $libitem->id); ?>
    </span>
    <a id="<?php echo 'edit_podcast_episode_' . $libitem->id; ?>" onclick="showEditDialog('podcast_episode_row', '<?php echo $libitem->id; ?>', '<?php echo 'edit_podcast_episode_' . $libitem->id; ?>', '<?php echo addslashes(T_('Podcast Episode Edit')); ?>', 'podcast_episode_', '<?php echo '&browse_id=' . $browse->getId(); ?>')">
        <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
    </a>
    <?php
}
if (Catalog::can_remove($libitem)) { ?>
    <a id="<?php echo 'delete_podcast_episode_' . $libitem->id; ?>" href="<?php echo $web_path; ?>/podcast_episode.php?action=delete&podcast_episode_id=<?php echo $libitem->id; ?>">
        <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
    </a>
    <?php } ?>
</td>
