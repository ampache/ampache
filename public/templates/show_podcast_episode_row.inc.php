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
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var Podcast_Episode $libitem */

?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
        if (AmpConfig::get('directplay') && !empty($libitem->file)) {
            echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $libitem->id, 'play', T_('Play'), 'play_podcast_episode_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $libitem->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_podcast_episode_' . $libitem->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $libitem->id . '&append=true', 'play_add', T_('Play last'), 'addplay_podcast_episode_' . $libitem->id);
            }
        } ?>
    </div>
</td>
<?php
if ($browse->is_mashup()) {
            $name = scrub_out($libitem->f_full_title); ?>
    <td class="<?php echo $cel_cover; ?>">
        <?php
        $thumb = (isset($browse) && !$browse->is_grid_view()) ? 11 : 1;
            Art::display('podcast', $libitem->podcast, $name, $thumb, AmpConfig::get('web_path') . '/albums.php?action=show&podcast=' . $libitem->podcast); ?>
    </td>
<?php
        } ?>
<td class="cel_title"><?php echo $libitem->f_link; ?></td>
<td class="cel_add">
    <span class="cel_item_add">
<?php
    echo Ajax::button('?action=basket&type=podcast_episode&id=' . $libitem->id, 'add', T_('Add to Temporary Playlist'), 'add_' . $libitem->id);
    if (Access::check('interface', 25)) { ?>
        <a id="<?php echo 'add_playlist_' . $libitem->id ?>" onclick="showPlaylistDialog(event, 'podcast_episode', '<?php echo $libitem->id ?>')">
            <?php echo Ui::get_icon('playlist_add', T_('Add to playlist')); ?>
        </a>
    <?php
    } ?>
    </span>
</td>
<td class="cel_podcast"><?php echo $libitem->f_podcast_link; ?></td>
<td class="<?php echo $cel_time; ?>"><?php echo $libitem->f_time; ?></td>
<?php if (AmpConfig::get('show_played_times')) { ?>
    <td class="<?php echo $cel_counter; ?> optional"><?php echo $libitem->object_cnt; ?></td>
    <?php
} ?>
<td class="cel_pubdate optional"><?php echo $libitem->f_pubdate; ?></td>
<td class="cel_state optional"><?php echo $libitem->f_state; ?></td>
<?php
    if ($show_ratings) { ?>
        <td class="cel_ratings">
            <?php if (AmpConfig::get('ratings')) { ?>
                <span class="cel_rating" id="rating_<?php echo $libitem->id; ?>_podcast_episode">
                    <?php echo Rating::show($libitem->id, 'podcast_episode'); ?>
                </span>
            <?php
            } ?>

            <?php if (AmpConfig::get('userflags')) { ?>
                <span class="cel_userflag" id="userflag_<?php echo $libitem->id; ?>_podcast_episode">
                    <?php echo Userflag::show($libitem->id, 'podcast_episode'); ?>
                </span>
            <?php
            } ?>
        </td>
    <?php
    } ?>
<td class="cel_action">
    <?php if (Access::check_function('download') && !empty($libitem->file)) { ?>
            <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&amp;podcast_episode_id=<?php echo $libitem->id; ?>"><?php echo Ui::get_icon('download', T_('Download')); ?></a>
        <?php
} ?>
<?php
    if (Access::check('interface', 50)) { ?>
    <span id="button_sync_<?php echo $libitem->id; ?>">
        <?php echo Ajax::button('?page=podcast&action=sync&podcast_episode_id=' . $libitem->id, 'file_refresh', T_('Sync'), 'sync_podcast_episode_' . $libitem->id); ?>
    </span>
    <a id="<?php echo 'edit_podcast_episode_' . $libitem->id ?>" onclick="showEditDialog('podcast_episode_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_podcast_episode_' . $libitem->id ?>', '<?php echo T_('Podcast Episode Edit') ?>', 'podcast_episode_')">
        <?php echo Ui::get_icon('edit', T_('Edit')); ?>
    </a>
    <?php
    }
    if (Catalog::can_remove($libitem)) { ?>
    <a id="<?php echo 'delete_podcast_episode_' . $libitem->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/podcast_episode.php?action=delete&podcast_episode_id=<?php echo $libitem->id; ?>">
        <?php echo Ui::get_icon('delete', T_('Delete')); ?>
    </a>
    <?php
    } ?>
</td>