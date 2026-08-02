<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// show_song_preview_row.inc.php

/** @var Song_Preview $libitem */

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\Song_Preview;

?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php
        if (AmpConfig::get('directplay') && !empty($libitem->file)) {
            echo Ajax::button('?page=stream&action=directplay&object_type=song_preview&object_id=' . $libitem->id, 'play_circle', T_('Play'), 'play_song_preview_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=song_preview&object_id=' . $libitem->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_song_preview_' . $libitem->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=song_preview&object_id=' . $libitem->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_song_preview_' . $libitem->id);
            }
        } ?>
    </div>
</td>
<td class="cel_song"><?php echo scrub_out($libitem->title); ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=song_preview&id=' . $libitem->id, 'new_window', T_('Add to Temporary Playlist'), 'add_song_preview_' . $libitem->id); ?>
    </span>
</td>
<td class="cel_artist"><?php echo $libitem->get_f_parent_link(); ?></td>
<td class="cel_album"><?php echo $libitem->get_f_album_link(); ?></td>
<td class="cel_track"><?php echo $libitem->track; ?></td>
<td class="cel_disk"><?php echo $libitem->disk; ?></td>
