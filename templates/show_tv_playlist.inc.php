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
<h3><?php echo _('Current Playlist'); ?></h3>
<table cellspacing="0">
<tr class="table-header">
	<td><?php echo _('Action'); ?></td>
	<td><?php echo _('Votes'); ?></td>
	<td><?php echo _('Song'); ?></td>
</tr>
<?php 
foreach($songs as $row_id=>$song_id) { 
	$song = new Song($song_id);
	$song->format_song();
?>
<tr>
	<td>
	<?php if ($tmp_playlist->has_vote($song_id)) { ?>
		<input class="button" type="button" value="-" onclick="ajaxPut('<?php echo conf('ajax_url'); ?>?action=vote&amp;object_id=<?php echo $song_id; ?>&amp;vote=-1<?php echo conf('ajax_info'); ?>')" />
	<?php } else { ?>
		<input class="button" type="button" value="+" onclick="ajaxPut('<?php echo conf('ajax_url'); ?>?action=vote&amp;object_id=<?php echo $song_id; ?>&amp;vote=1<?php echo conf('ajax_info'); ?>')" />
	<?php } ?>
	</td>
	<td><?php echo scrub_out($tmp_playlist->get_vote($row_id)); ?></td>
	<td><?php echo scrub_out($song->title . ' / ' . $song->get_album_name()); ?></td>
</tr>
<?php } ?>
</table>
