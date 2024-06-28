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
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var Live_Stream $libitem */
/** @var Ampache\Repository\Model\Browse $browse */
/** @var string $cel_cover */
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) {
        echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $libitem->id, 'play', T_('Play'), 'play_live_stream_' . $libitem->id);
        if (Stream_Playlist::check_autoplay_next()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $libitem->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_live_stream_' . $libitem->id);
        }
        if (Stream_Playlist::check_autoplay_append()) {
            echo Ajax::button('?page=stream&action=directplay&object_type=live_stream&object_id=' . $libitem->id . '&append=true', 'play_add', T_('Play last'), 'addplay_live_stream_' . $libitem->id);
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
        <?php echo Ajax::button('?action=basket&type=live_stream&id=' . $libitem->id, 'add', T_('Add to Temporary Playlist'), 'playlist_add_' . $libitem->id);
if (Access::check('interface', 25)) { ?>
            <a id="<?php echo 'add_playlist_' . $libitem->id; ?>" onclick="showPlaylistDialog(event, '<?php echo 'live_stream'; ?>', '<?php echo $libitem->id; ?>')">
                <?php echo Ui::get_icon('playlist_add', T_('Add to playlist')); ?>
            </a>
        <?php } ?>
    </span>
</td>
<td class="cel_siteurl"><?php echo $libitem->f_site_url_link; ?></td>
<td class="cel_codec"><?php echo $libitem->codec; ?></td>
<td class="cel_action">
    <?php
if (Access::check('interface', 50)) { ?>
        <a id="<?php echo 'edit_live_stream_' . $libitem->id; ?>" onclick="showEditDialog('live_stream_row', '<?php echo $libitem->id; ?>', '<?php echo 'edit_live_stream_' . $libitem->id; ?>', '<?php echo addslashes(T_('Live Stream Edit')); ?>', 'live_stream_')">
            <?php echo Ui::get_icon('edit', T_('Edit')); ?>
        </a>
        <?php
}
if (Access::check('interface', 75)) {
    echo Ajax::button('?page=browse&action=delete_object&type=live_stream&id=' . $libitem->id, 'delete', T_('Delete'), 'delete_live_stream_' . $libitem->id);
} ?>
</td>
