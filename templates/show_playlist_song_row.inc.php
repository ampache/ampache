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
<td class="cel_add"><?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add',_('Add'),'playlist_add_' . $song->id); ?></td>
<td class="cel_track"><?php echo $playlist_track; ?></td>
<td class="cel_song"><?php echo $song->f_link; ?></td>
<td class="cel_artist"><?php echo $song->f_artist_link; ?></td>
<td class="cel_album"><?php echo $song->f_album_link; ?></td>
<td class="cel_genre"><?php echo $song->f_genre_link; ?></td>
<td class="cel_track"><?php echo $song->f_track; ?></td>
<td class="cel_time"><?php echo $song->f_time; ?></td>
<td class="cel_action">
	<?php if (Config::get('download')) { ?>
	<a href="<?php echo Config::get('web_path'); ?>/stream.php?action=download&amp;song_id=<?php echo $song->id; ?>">
		<?php echo get_user_icon('download',_('Download')); ?>
	</a>
	<?php } ?>
	<?php if ($playlist->has_access()) { ?>
		<?php echo Ajax::button('?page=playlist&action=edit_track&playlist_id=' . $playlist->id . '&track_id=' . $object['track_id'],'edit',_('Edit'),'track_edit_' . $object['track_id']); ?>
		<?php echo Ajax::button('?page=playlist&action=delete_track&playlist_id=' . $playlist->id . '&track_id=' . $object['track_id'],'delete',_('Delete'),'track_del_' . $object['track_id']); ?>
	<?php } ?>
</td>
