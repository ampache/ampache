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
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var PodcastEpisodeInterface $libitem */
/** @var MediaDeletionCheckerInterface $mediaDeletionChecker */
$podcast   = $libitem->getPodcast();
$episodeId = $libitem->getId();

global $dic;
$mediaDeletionChecker = $dic->get(MediaDeletionCheckerInterface::class);

?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
        if (AmpConfig::get('directplay') && !empty($libitem->getFile())) {
            echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episodeId, 'play', T_('Play'), 'play_podcast_episode_' . $episodeId);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episodeId . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_podcast_episode_' . $episodeId);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episodeId . '&append=true', 'play_add', T_('Play last'), 'addplay_podcast_episode_' . $episodeId);
            }
        } ?>
    </div>
</td>
<td class="cel_title"><?php echo $libitem->getLinkFormatted(); ?></td>
<td class="cel_add">
    <span class="cel_item_add">
<?php
    echo Ajax::button('?action=basket&type=podcast_episode&id=' . $episodeId, 'add', T_('Add to Temporary Playlist'), 'add_' . $episodeId);
    if (Access::check('interface', 25)) { ?>
        <a id="<?php echo 'add_playlist_' . $episodeId ?>" onclick="showPlaylistDialog(event, 'podcast_episode', '<?php echo $episodeId ?>')">
            <?php echo Ui::get_icon('playlist_add', T_('Add to playlist')); ?>
        </a>
    <?php
    } ?>
    </span>
</td>
<td class="cel_podcast"><?php echo $podcast->getLinkFormatted(); ?></td>
<td class="<?php echo $cel_time; ?>"><?php echo $libitem->getDurationFormatted(); ?></td>
<?php $played_times = $libitem->getObjectCount();
if ($played_times !== null) { ?>
    <td class="<?php echo $cel_counter; ?> optional"><?php echo $played_times ?></td>
    <?php
} ?>
<td class="cel_pubdate"><?php echo $libitem->getPublicationDateFormatted(); ?></td>
<td class="cel_state"><?php echo $libitem->getStateFormatted(); ?></td>
<?php
    if ($show_ratings) { ?>
        <td class="cel_ratings">
            <?php if (AmpConfig::get('ratings')) { ?>
                <span class="cel_rating" id="rating_<?php echo $episodeId; ?>_podcast_episode">
                    <?php echo Rating::show($episodeId, 'podcast_episode'); ?>
                </span>
            <?php
            } ?>

            <?php if (AmpConfig::get('userflags')) { ?>
                <span class="cel_userflag" id="userflag_<?php echo $episodeId; ?>_podcast_episode">
                    <?php echo Userflag::show($episodeId, 'podcast_episode'); ?>
                </span>
            <?php
            } ?>
        </td>
    <?php
    } ?>
<td class="cel_action">
    <?php if (Access::check_function('download') && !empty($libitem->getFile())) { ?>
            <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&amp;podcast_episode_id=<?php echo $episodeId; ?>"><?php echo Ui::get_icon('download', T_('Download')); ?></a>
        <?php
} ?>
<?php
    if (Access::check('interface', 50)) { ?>
    <span id="button_sync_<?php echo $episodeId; ?>">
        <?php echo Ajax::button('?page=podcast&action=sync&podcast_episode_id=' . $episodeId, 'file_refresh', T_('Sync'), 'sync_podcast_episode_' . $episodeId); ?>
    </span>
    <a id="<?php echo 'edit_podcast_episode_' . $episodeId ?>" onclick="showEditDialog('podcast_episode_row', '<?php echo $episodeId ?>', '<?php echo 'edit_podcast_episode_' . $episodeId ?>', '<?php echo T_('Podcast Episode Edit') ?>', 'podcast_episode_')">
        <?php echo Ui::get_icon('edit', T_('Edit')); ?>
    </a>
    <?php
    }
    if ($mediaDeletionChecker->mayDelete($libitem, Core::get_global('user')->getId())) { ?>
    <a id="<?php echo 'delete_podcast_episode_' . $episodeId ?>" href="<?php echo AmpConfig::get('web_path'); ?>/podcast_episode.php?action=delete&podcast_episode_id=<?php echo $episodeId; ?>">
        <?php echo Ui::get_icon('delete', T_('Delete')); ?>
    </a>
    <?php
    } ?>
</td>