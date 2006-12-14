<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
/* Some defaults */
$web_path = conf('web_path'); 
?>
<table cellspacing="0">
<?php
if (!count($songs)) { 
	$playlist = new Playlist($tmp_playlist->base_playlist);
?>
<tr>
	<td>
		<?php echo _('Playing from base Playlist'); ?>: 
		<a href="<?php echo $web_path; ?>/playlist.php?action=show_playlist&amp;playlist_id=<?php echo $playlist->id; ?>">
		<?php echo scrub_out($playlist->name); ?>
		</a>
	</td>
</tr>
<?php
} // if no songs
/* Else we have songs */
else {
?>
<tr class="table-header">
	<td><?php echo _('Action'); ?></td>
	<td><?php echo _('Votes'); ?></td>
	<td><?php echo _('Song'); ?></td>
	<td><?php echo _('Time'); ?></td>
	<?php if ($GLOBALS['user']->has_access(100)) { ?>
	<td><?php echo _('Admin'); ?></td>
	<?php } ?>
</tr>
<?php 


foreach($songs as $row_id=>$song_id) { 
	$song = new Song($song_id);
	$song->format_song();
?>
<tr class="<?php echo flip_class(); ?>">
	<td>
	<?php if ($tmp_playlist->has_vote($song_id)) { ?>
		<input class="button" type="button" value="-" onclick="ajaxPut('<?php echo conf('ajax_url'); ?>?action=vote&amp;object_id=<?php echo $song_id; ?>&amp;vote=-1<?php echo conf('ajax_info'); ?>')" />
	<?php } else { ?>
		<input class="button" type="button" value="+" onclick="ajaxPut('<?php echo conf('ajax_url'); ?>?action=vote&amp;object_id=<?php echo $song_id; ?>&amp;vote=1<?php echo conf('ajax_info'); ?>')" />
	<?php } ?>
	</td>
	<td><?php echo scrub_out($tmp_playlist->get_vote($row_id)); ?></td>
	<td><?php echo $song->f_link . " / " . $song->f_album_link . " / " . $song->f_artist_link; ?></td>
	<td><?php echo $song->f_time; ?></td>
	<?php if ($GLOBALS['user']->has_access(100)) { ?>
	<td>
		<span onclick="ajaxPut('<?php echo conf('ajax_url'); ?>?action=tv_admin&amp;cmd=delete&amp;track_id=<?php echo $song_id; ?><?php echo conf('ajax_info'); ?>')" />
		<?php echo get_user_icon('delete'); ?>
		</span>
	</td>
	<?php } ?>
</tr>
<?php 
	} // end foreach 
} // end else
?>
</table>
