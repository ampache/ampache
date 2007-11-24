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
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_action" />
  <col id="col_votes" />
  <col id="col_song" />
  <col id="col_time" />
  <?php if ($GLOBALS['user']->has_access(100)) { ?>
  <col id="col_admin" />
  <?php } ?>
</colgroup>
<?php
if (!count($objects)) { 
	$playlist = new Playlist($democratic->base_playlist);
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
<tr class="th-top">
	<th class="cel_action"><?php echo _('Action'); ?></th>
	<th class="cel_votes"><?php echo _('Votes'); ?></th>
	<th class="cel_song"><?php echo _('Song'); ?></th>
	<th class="cel_time"><?php echo _('Time'); ?></th>
	<?php if ($GLOBALS['user']->has_access(100)) { ?>
	<th class="cel_admin"><?php echo _('Admin'); ?></th>
	<?php } ?>
</tr>
<?php 

foreach($objects as $row_id=>$object_data) { 
	$song = new Song($object_data['0']);
	$song->format();
?>
<tr class="<?php echo flip_class(); ?>">
	<td class="cel_action">
	<?php if ($democratic->has_vote($song_id)) { ?>
	<?php } else { ?>
	<?php } ?>
	</td>
	<td class="cel_votes"><?php echo scrub_out($democratic->get_vote($row_id)); ?></td>
	<td class="cel_song"><?php echo $song->f_link . " / " . $song->f_album_link . " / " . $song->f_artist_link; ?></td>
	<td class="cel_time"><?php echo $song->f_time; ?></td>
	<?php if ($GLOBALS['user']->has_access(100)) { ?>
	<td class="cel_admin">
	<?php echo Ajax::button('?page=democratic&action=delete&row_id=' . $row_id,'delete',_('Delete'),'delete_row_' . $row_id); ?>
	</td>
	<?php } ?>
</tr>
<?php 
	} // end foreach
?> 
<tr class="th-bottom">
	<th class="cel_action"><?php echo _('Action'); ?></th>
	<th class="cel_votes"><?php echo _('Votes'); ?></th>
	<th class="cel_song"><?php echo _('Song'); ?></th>
	<th class="cel_time"><?php echo _('Time'); ?></th>
	<?php if ($GLOBALS['user']->has_access(100)) { ?>
	<th class="cel_admin"><?php echo _('Admin'); ?></th>
	<?php } ?>
</tr>
<?php
} // end else
?>
</table>
