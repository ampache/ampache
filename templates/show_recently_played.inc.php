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
	<td><?php echo _('Album'); ?></td>
	<td><?php echo _('Artist'); ?></td>
</tr>
<?php foreach ($data as $row) { 
	$row_user = new User($row['user']);
	$song = new Song($row['object_id']); 
	$song->format_song(); 
?>
<tr>
	<td><?php echo scrub_out($row_user->fullname); ?></td>
	<td><?php echo $song->f_link; ?></td>
	<td><?php echo $song->f_album_link; ?></td>
	<td><?php echo $song->f_artist_link; ?></td>
</tr>
<?php } ?>
</table>
<?php show_box_bottom(); ?>
