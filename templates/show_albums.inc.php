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
$web_path = Config::get('web_path');
$ajax_url = Config::get('ajax_url'); 
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellspacing="0" cellpadding="0">
<colgroup>
  <col id="br_add" />
  <col id="br_cover" />
  <col id="br_album" />
  <col id="br_artist" />
  <col id="br_songs" />
  <col id="br_year" />
  <col id="br_action" />
</colgroup>
<tr class="table-header th-top">
	<th><?php echo _('Add'); ?></th>
	<?php if (Browse::get_filter('show_art')) { ?>
	<th><?php echo _('Cover'); ?></th>
	<?php } ?>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=name',_('Album'),'album_sort_name'); ?></th>
	<th><?php echo _('Artist'); ?></th>
	<th><?php echo _('Songs'); ?></th>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=year',_('Year'),'album_sort_year'); ?></th>
	<th><?php echo _('Actions'); ?></th>
</tr>
<?php 
	/* Foreach through the albums */
	foreach ($object_ids as $album_id) { 
		$album = new Album($album_id); 
		$album->format(); 
?>
<tr id="album_<?php echo $album->id; ?>" class="<?php echo flip_class(); ?>">
	<?php require Config::get('prefix') . '/templates/show_album_row.inc.php'; ?> 
</tr>
<?php } //end foreach ($albums as $album) ?>
<tr class="table-header th-bottom">
	<th><?php echo _('Add'); ?></th>
	<?php if (Browse::get_filter('show_art')) { ?>
	<th><?php echo _('Cover'); ?></th>
	<?php } ?>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=name',_('Album'),'album_sort_name'); ?></th>
	<th><?php echo _('Artist'); ?></th>
	<th><?php echo _('Songs'); ?></th>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=year',_('Year'),'album_sort_year'); ?></th>
	<th><?php echo _('Actions'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
