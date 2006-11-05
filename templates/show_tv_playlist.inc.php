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
<h3><?php echo _('Current Playlist'); ?></h3>
<table cellspacing="0">
<tr class="table-header">
	<td><?php echo _('Votes'); ?></td>
	<td><?php echo _('Song'); ?></td>
	<td><?php echo _('Length'); ?></td>
</tr>
<?php 
foreach($songs as $row_id=>$song_id) { 
	$song = new Song($song_id);
	$song->format_song();
?>
<tr>
	<td><?php echo scrub_out($tmp_playlist->get_vote($row_id)); ?></td>
	<td><?php echo scrub_out($song->title); ?></td>
	<td><?php echo scrub_out($song->length); ?></td>
</tr>
<?php } ?>
</table>
