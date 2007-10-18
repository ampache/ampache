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
<?php show_box_top(_('Current Playlist')); ?>
<table class="table-data" cellspacing="0">
<tr class="table-header">
	<th><?php echo _('Track'); ?></th>
	<th><?php echo _('Name'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php 
foreach ($objects as $object) { 
	$class = '';
	if ($status['track'] == $object['track']) { $class=' class="lp_current"'; } 	
?>
<tr class="<?php echo flip_class(); ?>" id="localplay_playlist_<?php echo $object['id']; ?>">
	<td>
		<?php echo scrub_out($object['track']); ?>
	</td>
	<td<?php echo $class; ?>>
		<?php echo $localplay->format_name($object['name'],$object['id']); ?>
	</td>
	<td>
	<?php echo Ajax::button('?page=localplay&action=delete_track&id=' . intval($object['id']),'delete',_('Delete'),'localplay_delete_' . intval($object['id'])); ?>
	</td>
</tr>
<?php } if (!count($objects)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="3"><span class="error"><?php echo _('No Records Found'); ?></span></td>
</tr>
<?php } ?>
</table>
<?php show_box_bottom(); ?>
