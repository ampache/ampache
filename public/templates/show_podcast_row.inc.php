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
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var Podcast $libitem */
/** @var bool $show_ratings */
/** @var string $cel_cover */
/** @var string $cel_counter */
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
        if (AmpConfig::get('directplay')) {
            echo Ajax::button('?page=stream&action=directplay&object_type=podcast&object_id=' . $libitem->getId(), 'play_circle', T_('Play'), 'play_podcast_' . $libitem->getId());
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=podcast&object_id=' . $libitem->getId() . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_podcast_' . $libitem->getId());
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=podcast&object_id=' . $libitem->getId() . '&append=true', 'low_priority', T_('Play last'), 'addplay_podcast_' . $libitem->getId());
            }
        } ?>
    </div>
</td>
<td class="<?php echo $cel_cover; ?>">
    <?php Art::display('podcast', $libitem->getId(), (string)$libitem->get_fullname(), 2, $libitem->get_link()); ?>
</td>
<td class="cel_title"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_siteurl"><?php echo "<a target=\"_blank\" href=\"" . $libitem->getWebsite() . "\">" . $libitem->getWebsite() . "</a>"; ?></td>
<td class="cel_episodes"><?php echo $libitem->getEpisodeCount(); ?></td>
<?php if (AmpConfig::get('show_played_times')) { ?>
    <td class="<?php echo $cel_counter; ?> optional"><?php echo $libitem->getTotalCount(); ?></td>
<?php } ?>
<?php if ($show_ratings) { ?>
        <td class="cel_ratings">
            <?php if (AmpConfig::get('ratings')) { ?>
                <span class="cel_rating" id="rating_<?php echo $libitem->getId(); ?>_podcast"><?php echo Rating::show($libitem->getId(), 'podcast'); ?></span>
                <span class="cel_userflag" id="userflag_<?php echo $libitem->getId(); ?>_podcast"><?php echo Userflag::show($libitem->getId(), 'podcast'); ?></span>
            <?php } ?>
        </td>
    <?php } ?>
<td class="cel_action">
<?php
    if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
    <a id="<?php echo 'edit_podcast_' . $libitem->getId(); ?>" onclick="showEditDialog('podcast_row', '<?php echo $libitem->getId(); ?>', '<?php echo 'edit_podcast_' . $libitem->getId(); ?>', '<?php echo addslashes(T_('Podcast Edit')); ?>', 'podcast_')">
        <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
    </a>
    <span id="button_sync_<?php echo $libitem->getId(); ?>">
        <?php echo Ajax::button('?page=podcast&action=syncPodcast&podcast_id=' . $libitem->getId(), 'sync', T_('Sync'), 'sync_podcast_' . $libitem->getId()); ?>
    </span>
    <?php
    }
if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) { ?>
    <a id="<?php echo 'delete_podcast_' . $libitem->getId(); ?>" href="<?php echo AmpConfig::get('web_path'); ?>/podcast.php?action=delete&podcast_id=<?php echo $libitem->getId(); ?>">
        <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
    </a>
    <?php } ?>
</td>
