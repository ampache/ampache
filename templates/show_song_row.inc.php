<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<td class="cel_add">
	<?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add',_('Add'),'add_' . $song->id); ?>
</td>
<td class="cel_song"><a href="<?php echo Song::play_url($song->id); ?>" title="<?php echo scrub_out($song->title); ?>"><?php echo $song->f_title; ?></a></td>
<td class="cel_artist"><?php echo $song->f_artist_link; ?></td>
<td class="cel_album"><?php echo $song->f_album_link; ?></td>
<td class="cel_tags"><?php echo $song->f_tags; ?></td>
<td class="cel_track"><?php echo $song->f_track; ?></td>
<td class="cel_time"><?php echo $song->f_time; ?></td>
<?php if (Config::get('ratings')) { ?>
<td class="cel_rating" id="rating_<?php echo $song->id; ?>_song"><?php Rating::show($song->id,'song'); ?></td>
<?php } ?>
<td class="cel_action">
	<a href="<?php echo $song->link; ?>"><?php echo get_user_icon('preferences',_('Song Information')); ?></a>
	<?php if (Config::get('shoutbox')) { ?>
                <a href="<?php echo Config::get('web_path'); ?>/shout.php?action=show_add_shout&amp;type=song&amp;id=<?php echo $song->id; ?>">
                <?php echo get_user_icon('comment',_('Post Shout')); ?>
                </a>
	<?php } ?>
	<?php if (Access::check_function('download')) { ?>
	<a href="<?php echo Config::get('web_path'); ?>/stream.php?action=download&amp;song_id=<?php echo $song->id; ?>">
		<?php echo get_user_icon('download',_('Download')); ?>
	</a>
	<?php } ?>
	<?php if (Access::check('interface','75')) { ?>
		<?php echo Ajax::button('?action=show_edit_object&type=song&id=' . $song->id,'edit',_('Edit'),'edit_song_' . $song->id); ?>
		<?php $icon = $song->enabled ? 'disable' : 'enable'; ?>
		<?php $button_flip_state_id = 'button_flip_state_' . $song_id; ?>
		<span id="<?php echo($button_flip_state_id); ?>">
		<?php echo Ajax::button('?page=song&action=flip_state&song_id=' . $song->id,$icon,_(ucfirst($icon)),'flip_song_' . $song->id); ?>
		</span> 
	<?php } ?>
</td>
