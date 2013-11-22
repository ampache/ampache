<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
<?php if (Config::get('directplay')) { ?>
<td class="cel_directplay">
    <?php echo Ajax::button('?page=stream&action=directplay&playtype=song&song_id=' . $song->id,'play', T_('Play song'),'play_song_' . $song->id); ?>
</td>
<?php } ?>
<td class="cel_add">
    <?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add', T_('Add'),'add_' . $song->id); ?>
</td>
<td class="cel_song"><?php echo $song->f_link; ?></a></td>
<td class="cel_artist"><?php echo $song->f_artist_link; ?></td>
<td class="cel_album"><?php echo $song->f_album_link; ?></td>
<td class="cel_tags"><?php echo $song->f_tags; ?></td>
<td class="cel_track"><?php echo $song->f_track; ?></td>
<td class="cel_time"><?php echo $song->f_time; ?></td>
<?php if (Config::get('ratings')) { ?>
<td class="cel_rating" id="rating_<?php echo $song->id; ?>_song"><?php Rating::show($song->id,'song'); ?></td>
<?php } ?>
<?php if (Config::get('userflags')) { ?>
<td class="cel_userflag" id="userflag_<?php echo $song->id; ?>_song"><?php Userflag::show($song->id,'song'); ?></td>
<?php } ?>
<td class="cel_action">
    <a href="<?php echo $song->link; ?>"><?php echo UI::get_icon('preferences', T_('Song Information')); ?></a>
    <?php if (Config::get('shoutbox')) { ?>
                <a href="<?php echo Config::get('web_path'); ?>/shout.php?action=show_add_shout&amp;type=song&amp;id=<?php echo $song->id; ?>">
                <?php echo UI::get_icon('comment', T_('Post Shout')); ?>
                </a>
    <?php } ?>
    <?php if (Access::check_function('download')) { ?>
    <a href="<?php echo Config::get('web_path'); ?>/stream.php?action=download&amp;song_id=<?php echo $song->id; ?>"><?php echo UI::get_icon('download', T_('Download')); ?></a><?php } ?>
    <?php if (Access::check('interface','75')) { ?>
        <a id="<?php echo 'edit_song_'.$song->id ?>" onclick="showEditDialog('song_row', '<?php echo $song->id ?>', '<?php echo 'edit_song_'.$song->id ?>', '<?php echo T_('Song edit') ?>', '<?php echo T_('Save') ?>', '<?php echo T_('Cancel') ?>')">
            <?php echo UI::get_icon('edit', T_('Edit')); ?>
        </a>
        <?php $icon = $song->enabled ? 'disable' : 'enable'; ?>
        <?php $button_flip_state_id = 'button_flip_state_' . $song_id; ?>
        <span id="<?php echo($button_flip_state_id); ?>">
        <?php echo Ajax::button('?page=song&action=flip_state&song_id=' . $song->id,$icon, T_(ucfirst($icon)),'flip_song_' . $song->id); ?>
        </span>
    <?php } ?>
</td>
