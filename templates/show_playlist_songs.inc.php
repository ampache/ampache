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

// First let's setup some vars we're going to use a lot
$web_path = Config::get('web_path'); 
$ajax_url = Config::get('ajax_url'); 
?>
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
	<td colspan="8">
	<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
	</td>
</tr>
<tr class="table-header">
	<th>&nbsp;</th>
	<th><?php echo _('Track'); ?></th>
	<th><?php echo _('Song Title'); ?></th>
	<th><?php echo _('Artist'); ?></th>
	<th><?php echo _('Album'); ?></th>
	<th><?php echo _('Genre'); ?></th>
	<th><?php echo _('Track'); ?></th>
	<th><?php echo _('Time'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php 
	foreach ($object_ids as $object) { 
		$song = new Song($object['object_id']); 
		$song->format(); 
		$playlist_track = $object['track'];
?>
<tr class="<?php echo flip_class(); ?>" id="song_<?php echo $song->id; ?>">
	<?php require Config::get('prefix') . '/templates/show_playlist_song_row.inc.php'; ?> 
</tr>
<?php } ?>
<tr>
	<td colspan="8">
	<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
	</td>
</tr>
</table>
