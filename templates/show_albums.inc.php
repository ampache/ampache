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
<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
<tr class="table-header">
	<td colspan="5">
	</td>
</tr>
<tr class="table-header">
	<th><?php echo _('Add'); ?></th>
	<th><?php echo _('Album'); ?></th>
	<th><?php echo _('Artist'); ?></th>
	<th><?php echo _('Songs'); ?></th>
	<th><?php echo _('Year'); ?></th>
	<th><?php echo _('Actions'); ?></th>
</tr>
<?php 
	/* Foreach through the albums */
	foreach ($object_ids as $album_id) { 
		$album = new Album($album_id); 
		$album->format(); 
?>
<tr id="album_<?php echo $album->id; ?>" class="<?php echo flip_class(); ?>">
		<td>
			<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=album&amp;id=<?php echo $album->id; ?>');return true;" >
				<?php echo get_user_icon('add'); ?>
			</span>
			<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=album_random&amp;id=<?php echo $album->id; ?>');return true;" >
				<?php echo get_user_icon('random'); ?>
			</span>
		</td>
		<td><?php echo $album->f_name_link; ?></td>
		<td><?php echo $album->f_artist; ?></td>
		<td><?php echo $album->song_count; ?></td>
		<td><?php echo $album->year; ?></td>
		<td>
			<a href="<?php echo Config::get('web_path'); ?>/batch.php?action=album&amp;id=<?php echo $album->id; ?>">
				<?php echo get_user_icon('batch_download'); ?>
			</a>
			<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=album&amp;type=edit&amp;id=<?php echo $album->id; ?>');return true;" >
				<?php echo get_user_icon('edit'); ?>
			</span>
		</td>
</tr>
<?php } //end foreach ($albums as $album) ?>
</table>
