<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php' ?>
<table class="tabledata" cellspacing="0" cellpadding="0">
<colgroup>
  <col id="br_add" />
  <col id="br_playlist" />
  <col id="br_songs" />
  <col id="br_owner" />
  <col id="br_action" />
</colgroup>
<tr class="table-header th-top">
  <th><?php echo _('Add'); ?></th>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=name',_('Playlist Name'),'playlist_sort_name'); ?></th>
	<th><?php echo _('# Songs'); ?></th>
	<th><?php echo _('Owner'); ?></th>
	<th><?php echo _('Actions'); ?></th>
</tr>
<?php 
foreach ($object_ids as $playlist_id) { 
	$playlist = new Playlist($playlist_id); 
	$playlist->format(); 
	$count = $playlist->get_song_count(); 
?>
<tr class="<?php echo flip_class(); ?>" id="playlist_row_<?php echo $playlist->id; ?>">
	<?php require Config::get('prefix') . '/templates/show_playlist_row.inc.php'; ?> 
</tr>
<?php } // end foreach ($playlists as $playlist) ?>
<tr class="table-header th-bottom">
  <th><?php echo _('Add'); ?></th>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=name',_('Playlist Name'),'playlist_sort_name'); ?></th>
	<th><?php echo _('# Songs'); ?></th>
	<th><?php echo _('Owner'); ?></th>
	<th><?php echo _('Actions'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php' ?>
