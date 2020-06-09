<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */ ?>
<?php if (AmpConfig::get('echonest_api_key')) { ?>
<td class="cel_play">
    <?php
        if (AmpConfig::get('directplay') && $libitem->file) {
            echo Ajax::button('?page=stream&action=directplay&object_type=song_preview&object_id=' . $libitem->id, 'play_preview', T_('Play'), 'play_song_' . $libitem->id);
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=song_preview&object_id=' . $libitem->id . '&append=true', 'play_add_preview', T_('Play last'), 'addplay_song_' . $libitem->id);
            }
        } ?>
</td>
<?php
} ?>
<td class="cel_song"><?php echo $libitem->title; ?></td>
<?php if (AmpConfig::get('echonest_api_key')) { ?>
<td class="cel_add">
    <span class="cel_item_add">
        <?php
            if ($libitem->file) {
                echo Ajax::button('?action=basket&type=song_preview&id=' . $libitem->id, 'add', T_('Add to temporary playlist'), 'add_' . $libitem->id);
                if (Access::check('interface', '25')) { ?>
                    <a id="<?php echo 'add_playlist_' . $libitem->id ?>" onclick="showPlaylistDialog(event, 'song_preview', '<?php echo $libitem->id ?>')">
                    <?php echo UI::get_icon('playlist_add', T_('Add to playlist')); ?>
                    </a>
            <?php
                }
            } ?>
    </span>
</td>
<?php
        } ?>
<td class="cel_artist"><?php echo $libitem->f_artist_link; ?></td>
<td class="cel_album"><?php echo $libitem->f_album_link; ?></td>
<td class="cel_track"><?php echo $libitem->track; ?></td>
<td class="cel_disk"><?php echo $libitem->disk; ?></td>
