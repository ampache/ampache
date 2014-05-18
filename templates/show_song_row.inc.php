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
    <span class="cel_play_content"><?php if (isset($argument) && $argument) { echo '<b>'.$song->f_track.'</b>'; } ?></span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) { ?>
        <?php echo Ajax::button('?page=stream&action=directplay&playtype=song&song_id=' . $song->id, 'play', T_('Play'), 'play_song_' . $song->id); ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&playtype=song&song_id=' . $song->id . '&append=true', 'play_add', T_('Play last'), 'addplay_song_' . $song->id); ?>
        <?php } ?>
    <?php } ?>
    </div>
</td>
<td class="cel_song"><?php echo $song->f_link; ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add', T_('Add to temporary playlist'),'add_' . $song->id); ?>
        <a id="<?php echo 'add_playlist_'.$song->id ?>" onclick="showPlaylistDialog(event, 'song', '<?php echo $song->id ?>')">
            <?php echo UI::get_icon('playlist_add', T_('Add to existing playlist')); ?>
        </a>

        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo $song->show_custom_play_actions(); ?>
        <?php } ?>
    </span>
</td>
<td class="cel_artist"><?php echo $song->f_artist_link; ?></td>
<td class="cel_album"><?php echo $song->f_album_link; ?></td>
<td class="cel_tags" title="<?php echo $song->f_tags; ?>"><?php echo $song->f_tags; ?></td>
<td class="cel_time"><?php echo $song->f_time; ?></td>
<?php if (AmpConfig::get('ratings')) { ?>
<td class="cel_rating" id="rating_<?php echo $song->id; ?>_song"><?php Rating::show($song->id,'song'); ?></td>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<td class="cel_userflag" id="userflag_<?php echo $song->id; ?>_song"><?php Userflag::show($song->id,'song'); ?></td>
<?php } ?>
<td class="cel_action">
    <a href="<?php echo $song->link; ?>"><?php echo UI::get_icon('preferences', T_('Song Information')); ?></a>
    <?php if (AmpConfig::get('sociable')) { ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=song&id=<?php echo $song->id; ?>">
                <?php echo UI::get_icon('comment', T_('Post Shout')); ?>
                </a>
    <?php } ?>
    <?php if (AmpConfig::get('share')) { ?>
        <a href="<?php echo $web_path; ?>/share.php?action=show_create&type=song&id=<?php echo $song->id; ?>"><?php echo UI::get_icon('share', T_('Share')); ?></a>
    <?php } ?>
    <?php if (Access::check_function('download')) { ?>
    <a href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&song_id=<?php echo $song->id; ?>"><?php echo UI::get_icon('download', T_('Download')); ?></a><?php } ?>
    <?php if (Access::check('interface','75')) { ?>
        <a id="<?php echo 'edit_song_'.$song->id ?>" onclick="showEditDialog('song_row', '<?php echo $song->id ?>', '<?php echo 'edit_song_'.$song->id ?>', '<?php echo T_('Song edit') ?>', 'song_', 'refresh_song')">
            <?php echo UI::get_icon('edit', T_('Edit')); ?>
        </a>
        <?php $icon = $song->enabled ? 'disable' : 'enable'; ?>
        <?php $button_flip_state_id = 'button_flip_state_' . $song_id; ?>
        <span id="<?php echo($button_flip_state_id); ?>">
        <?php echo Ajax::button('?page=song&action=flip_state&song_id=' . $song->id,$icon, T_(ucfirst($icon)),'flip_song_' . $song->id); ?>
        </span>
    <?php } ?>
</td>
<?php if (isset($argument) && $argument) { ?>
<td class="cel_drag">
    <?php echo UI::get_icon('drag', T_('Reorder')); ?>
</td>
<?php } ?>
