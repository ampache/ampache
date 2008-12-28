<?php
/*

 Copyright (c) Ampache.org
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
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_add" />
	<?php if (Browse::get_filter('show_art')) { ?>
  <col id="col_cover" />
	<?php } ?>
  <col id="col_album" />
  <col id="col_artist" />
  <col id="col_songs" />
  <col id="col_year" />
  <col id="col_tags" />
  <col id="col_rating" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<?php if (Browse::get_filter('show_art')) { ?>
	<th class="cel_cover"><?php echo _('Cover'); ?></th>
	<?php } ?>
	<th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&type=album&sort=name',_('Album'),'album_sort_name'); ?></th>
	<th class="cel_artist"><?php echo Ajax::text('?page=browse&action=set_sort&type=album&sort=artist',_('Artist'),'album_sort_artist'); ?></th>
	<th class="cel_songs"><?php echo _('Songs'); ?></th>
	<th class="cel_year"><?php echo Ajax::text('?page=browse&action=set_sort&type=album&sort=year',_('Year'),'album_sort_year'); ?></th>
	<th class="cel_tags"><?php echo _('Tags'); ?></th>
	<th class="col_rating"><?php echo _('Rating'); ?></th>
	<th class="cel_action"><?php echo _('Actions'); ?></th>
</tr>
<?php 
	if (Config::get('ratings')) { 
		Rating::build_cache('album',$object_ids); 
	} 
	/* Foreach through the albums */
	foreach ($object_ids as $album_id) { 
		$album = new Album($album_id); 
		$album->format(); 
?>
<tr id="album_<?php echo $album->id; ?>" class="<?php echo flip_class(); ?>">
	<?php require Config::get('prefix') . '/templates/show_album_row.inc.php'; ?> 
</tr>
<?php } //end foreach ($albums as $album) ?>
<?php if (!count($object_ids)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="7"><span class="fatalerror"><?php echo _('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_add"><?php echo _('Add'); ?></th>
	<?php if (Browse::get_filter('show_art')) { ?>
	<th class="cel_cover"><?php echo _('Cover'); ?></th>
	<?php } ?>
	<th class="cel_album"><?php echo Ajax::text('?page=browse&action=set_sort&type=album&sort=name',_('Album'),'album_sort_name_bottom'); ?></th>
	<th class="cel_artist"><?php echo _('Artist'); ?></th>
	<th class="cel_songs"><?php echo _('Songs'); ?></th>
	<th class="cel_year"><?php echo Ajax::text('?page=browse&action=set_sort&type=album&sort=year',_('Year'),'album_sort_year_bottom'); ?></th>
	<th class="cel_tags"><?php echo _('Tags'); ?></th>
	<th class="col_rating"><?php echo _('Rating'); ?></th>
	<th class="cel_action"><?php echo _('Actions'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
