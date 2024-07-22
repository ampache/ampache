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
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\library_item $libitem */
/** @var Ampache\Repository\Model\Browse $browse */
/** @var Playlist|null $playlist */
/** @var int $playlist_track */
/** @var int $search */
/** @var array $object */
/** @var string $object_type */
/** @var string $cel_cover */
/** @var string $cel_time */
/** @var bool $show_ratings */

// Don't show disabled medias to normal users
if (!isset($libitem->enabled) || $libitem->enabled || Access::check('interface', 50)) { ?>
<td class="cel_play">
    <span class="cel_play_content"><?php echo '<b>' . $playlist_track . '</b>'; ?></span>
    <div class="cel_play_hover">
    <?php
    if (AmpConfig::get('directplay')) {
        echo Ajax::button('?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $libitem->getId(), 'play', T_('Play'), 'play_playlist_' . $object_type . '_' . $libitem->getId());
        if (Stream_Playlist::check_autoplay_next()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $libitem->getId() . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_' . $object_type . '_' . $libitem->getId());
        }
        if (Stream_Playlist::check_autoplay_append()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=' . $object_type . '&object_id=' . $libitem->getId() . '&append=true', 'play_add', T_('Play last'), 'addplay_' . $object_type . '_' . $libitem->getId());
        }
    } ?>
    </div>
</td>
<td class="<?php echo $cel_cover; ?>">
<div style="max-width: 80px;">
    <?php $thumb = ($browse->is_grid_view()) ? 3 : 11;
    $libitem->display_art($thumb); ?>
</div>
</td>
<td class="cel_title"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=' . $object_type . '&id=' . $libitem->getId(), 'add', T_('Add to Temporary Playlist'), 'playlist_add_' . $libitem->getId());
    if (Access::check('interface', 25)) { ?>
            <a id="<?php echo 'add_playlist_' . $libitem->getId(); ?>" onclick="showPlaylistDialog(event, '<?php echo $object_type; ?>', '<?php echo $libitem->getId(); ?>')">
                <?php echo Ui::get_icon('playlist_add', T_('Add to playlist')); ?>
            </a>
        <?php } ?>
    </span>
</td>
<td class="<?php echo $cel_time; ?>"><?php echo $libitem->f_time ?? ''; ?></td>
<?php if ($show_ratings) { ?>
    <td class="cel_ratings">
        <?php if (AmpConfig::get('ratings')) { ?>
            <span class="cel_rating" id="rating_<?php echo $libitem->getId(); ?>_<?php echo $object_type; ?>">
                <?php echo Rating::show($libitem->getId(), $object_type); ?>
            </span>
            <span class="cel_userflag" id="userflag_<?php echo $libitem->getId(); ?>_<?php echo $object_type; ?>">
                <?php echo Userflag::show($libitem->getId(), $object_type); ?>
            </span>
        <?php } ?>
    </td>
<?php } ?>
<td class="cel_action">
    <?php if (AmpConfig::get('download')) { ?>
    <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&<?php echo $object_type; ?>_id=<?php echo $libitem->getId(); ?>">
        <?php echo Ui::get_icon('download', T_('Download')); ?>
    </a>
    <?php
    }
    if (Access::check('interface', 25)) {
        if (AmpConfig::get('share')) {
            echo Share::display_ui($object_type, $libitem->getId(), false);
        }
    }
    if (isset($playlist) && $playlist->has_collaborate()) {
        echo Ajax::button('?page=playlist&action=delete_track&playlist_id=' . $playlist->id . '&browse_id=' . $browse->getId() . '&track_id=' . $object['track_id'], 'delete', T_('Delete'), 'track_del_' . $object['track_id']); ?>
    </td>
    <td class="cel_drag">
        <?php echo Ui::get_icon('drag', T_('Reorder')); ?>
            </td>
    <?php
    }
} ?>
