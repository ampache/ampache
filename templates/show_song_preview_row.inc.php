<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
?>
<?php if (AmpConfig::get('echonest_api_key')) { ?>
<td class="cel_play">
    <?php if (AmpConfig::get('directplay') && !empty($libitem->file)) { ?>
        <?php echo Ajax::button('?page=stream&action=directplay&object_type=song_preview&object_id=' . $libitem->id,'play_preview', T_('Play'),'play_song_' . $libitem->id); ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=song_preview&object_id=' . $libitem->id . '&append=true','play_add_preview', T_('Play last'),'addplay_song_' . $libitem->id); ?>
        <?php } ?>
<?php } ?>
</td>
<?php } ?>
<td class="cel_song"><?php echo $libitem->title; ?></td>
<?php if (AmpConfig::get('echonest_api_key')) { ?>
<td class="cel_add">
    <span class="cel_item_add">
        <?php if (!empty($libitem->file)) { ?>
            <?php echo Ajax::button('?action=basket&type=song_preview&id=' . $libitem->id,'add', T_('Add to temporary playlist'),'add_' . $libitem->id); ?>
            <a id="<?php echo 'add_playlist_'.$libitem->id ?>" onclick="showPlaylistDialog(event, 'song_preview', '<?php echo $libitem->id ?>')">
                <?php echo UI::get_icon('playlist_add', T_('Add to existing playlist')); ?>
            </a>
        <?php } ?>
    </span>
</td>
<?php } ?>
<td class="cel_artist"><?php echo $libitem->f_artist_link; ?></td>
<td class="cel_album"><?php echo $libitem->f_album_link; ?></td>
<td class="cel_track"><?php echo $libitem->track; ?></td>
<td class="cel_disk"><?php echo $libitem->disk; ?></td>
