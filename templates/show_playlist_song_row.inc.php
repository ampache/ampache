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
<td class="cel_play">
    <span class="cel_play_content"><?php echo '<b>'.$playlist_track.'</b>'; ?></span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) { ?>
         <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $libitem->id, 'play', T_('Play'),'play_playlist_song_' . $libitem->id); ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=song&object_id=' . $libitem->id . '&append=true','play_add', T_('Play last'),'addplay_song_' . $libitem->id); ?>
        <?php } ?>
<?php } ?>
    </div>
</td>
<td class="cel_song"><?php echo $libitem->f_link; ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=song&id=' . $libitem->id,'add', T_('Add to temporary playlist'),'playlist_add_' . $libitem->id); ?>
        <a id="<?php echo 'add_playlist_'.$libitem->id ?>" onclick="showPlaylistDialog(event, 'song', '<?php echo $libitem->id ?>')">
            <?php echo UI::get_icon('playlist_add', T_('Add to existing playlist')); ?>
        </a>
    </span>
</td>
<td class="cel_artist"><?php echo $libitem->f_artist_link; ?></td>
<td class="cel_album"><?php echo $libitem->f_album_link; ?></td>
<td class="cel_tags"><?php echo $libitem->f_tags; ?></td>
<td class="cel_time"><?php echo $libitem->f_time; ?></td>
<?php if (AmpConfig::get('ratings')) { ?>
<td class="cel_rating" id="rating_<?php echo $libitem->id; ?>_song"><?php Rating::show($libitem->id,'song'); ?></td>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<td class="cel_userflag" id="userflag_<?php echo $libitem->id; ?>_song"><?php Userflag::show($libitem->id,'song'); ?></td>
<?php } ?>
<td class="cel_action">
    <?php if (AmpConfig::get('download')) { ?>
    <a rel="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&amp;song_id=<?php echo $libitem->id; ?>">
        <?php echo UI::get_icon('download', T_('Download')); ?>
    </a>
    <?php } ?>
    <?php if (AmpConfig::get('share')) { ?>
        <a href="<?php echo AmpConfig::get('web_path'); ?>/share.php?action=show_create&type=song&id=<?php echo $libitem->id; ?>"><?php echo UI::get_icon('share', T_('Share')); ?></a>
    <?php } ?>
    <?php if (get_class($playlist) == "Playlist" && $playlist->has_access()) { ?>
        <?php echo Ajax::button('?page=playlist&action=delete_track&playlist_id=' . $playlist->id . '&track_id=' . $object['track_id'],'delete', T_('Delete'),'track_del_' . $object['track_id']); ?>
    <?php } ?>
</td>
<?php if (get_class($playlist) == "Playlist") { ?>
<td class="cel_drag">
    <?php echo UI::get_icon('drag', T_('Reorder')); ?>
</td>
<?php } ?>
