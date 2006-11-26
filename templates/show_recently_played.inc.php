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
?>
<?php show_box_top(_('Recently Played')); ?>
<table>
<tr class="table-header">
	<td><?php echo _('Username'); ?></td>
	<td><?php echo _('Song'); ?></td>
	<td><?php echo _('Date'); ?></td>
</tr>
<?php foreach ($data as $row) { 
	$row_user = new User($row['user']);
	$song = new Song($row['object_id']); 
	$song->format_song(); 
	/* Prepare the variables */
	$title = scrub_out(truncate_with_ellipse($song->title,'25'));
	$album = scrub_out(truncate_with_ellipse($song->f_album_full,'25'));
	$artist = scrub_out(truncate_with_ellipse($song->f_artist_full,'25'));
	$song_name = $title . ' - ' . $album . '/' . $artist;	
?>
<tr>
	<td><?php echo scrub_out($row_user->fullname); ?></td>
	<td><?php echo $song_name; ?></td>
	<td><?php echo date("d/m/Y H:i:s",$row['date']); ?></td>
</tr>
<?php } ?>
</table>
<?php show_box_bottom(); ?>
