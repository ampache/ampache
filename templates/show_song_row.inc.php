<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
<td>
	<?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add',_('Add'),'add_' . $song->id); ?>
</td>
<td><?php echo $song->f_link; ?></td>
<td><?php echo $song->f_artist_link; ?></td>
<td><?php echo $song->f_album_link; ?></td>
<td><?php echo $song->f_genre_link; ?></td>
<td><?php echo $song->f_track; ?></td>
<td><?php echo $song->f_time; ?></td>
<td>
	<?php if ($GLOBALS['user']->prefs['download']) { ?>
	<a href="<?php echo Config::get('web_path'); ?>/stream.php?action=download&amp;song_id=<?php echo $song->id; ?>">
		<?php echo get_user_icon('download',_('Download')); ?>
	</a>
	<?php } ?>

	<?php if ($GLOBALS['user']->has_access(100)) { ?>
	<?php echo Ajax::button('?action=show_edit_object&type=song&id=' . $song->id,'edit',_('Edit'),'edit_song_' . $song->id); ?>
	<?php } ?>
</td>
