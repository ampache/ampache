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
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var Live_Stream $libitem */
/** @var Ampache\Repository\Model\Browse $browse */
/** @var string $cel_cover */
/** @var bool $show_ratings */

$object_type = 'live_stream'; ?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) {
        echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $libitem->id, 'play_circle', T_('Play'), 'play_live_stream_' . $libitem->id);
        if (Stream_Playlist::check_autoplay_next()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $libitem->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_live_stream_' . $libitem->id);
        }
        if (Stream_Playlist::check_autoplay_append()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $libitem->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_live_stream_' . $libitem->id);
        }
    } ?>
    </div>
</td>
<td class="<?php echo $cel_cover; ?>">
    <?php $thumb = ($browse->is_grid_view()) ? 1 : 11;
$libitem->display_art($thumb); ?>
</td>
<td class="cel_streamname"><?php echo $libitem->get_f_link(); ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=live_stream&id=' . $libitem->id, 'new_window', T_('Add to Temporary Playlist'), 'playlist_add_' . $libitem->id);
if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
            <a id="<?php echo 'add_playlist_' . $libitem->id; ?>" onclick="showPlaylistDialog(event, '<?php echo 'live_stream'; ?>', '<?php echo $libitem->id; ?>')">
                <?php echo Ui::get_material_symbol('playlist_add', T_('Add to playlist')); ?>
            </a>
        <?php } ?>
    </span>
</td>
<td class="cel_siteurl"><?php echo $libitem->f_site_url_link; ?></td>
<td class="cel_codec"><?php echo $libitem->codec; ?></td>
<?php if ($show_ratings) { ?>
    <td class="cel_ratings">
        <?php if (AmpConfig::get('ratings')) { ?>
            <div class="rating">
                <span class="cel_rating" id="rating_<?php echo $libitem->getId(); ?>_<?php echo $object_type; ?>">
                    <?php echo Rating::show($libitem->getId(), $object_type); ?>
                </span>
                <span class="cel_userflag" id="userflag_<?php echo $libitem->getId(); ?>_<?php echo $object_type; ?>">
                    <?php echo Userflag::show($libitem->getId(), $object_type); ?>
                </span>
            </div>
        <?php } ?>
    </td>
<?php } ?>
<td class="cel_action">
    <?php
if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
        <a id="<?php echo 'edit_live_stream_' . $libitem->id; ?>" onclick="showEditDialog('live_stream_row', '<?php echo $libitem->id; ?>', '<?php echo 'edit_live_stream_' . $libitem->id; ?>', '<?php echo addslashes(T_('Live Stream Edit')); ?>', 'live_stream_')">
            <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
        </a>
        <?php
}
if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
    echo Ajax::button('?page=browse&action=delete_object&type=live_stream&id=' . $libitem->id, 'close', T_('Delete'), 'delete_live_stream_' . $libitem->id);
} ?>
</td>
