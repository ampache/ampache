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
$localplay = new Localplay(Config::get('localplay_controller'));
$localplay->connect(); 
$status = $localplay->status(); 
?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_track" />
  <col id="col_name" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
	<th class="cel_track"><?php echo _('Track'); ?></th>
	<th class="cel_name"><?php echo _('Name'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
<?php 
foreach ($object_ids as $object) { 
	$class = ' class="cel_name"';
	if ($status['track'] == $object['track']) { $class=' class="cel_name lp_current"'; } 	
?>
<tr class="<?php echo flip_class(); ?>" id="localplay_playlist_<?php echo $object['id']; ?>">
	<td class="cel_track">
		<?php echo scrub_out($object['track']); ?>
	</td>
	<td<?php echo $class; ?>>
		<?php echo $localplay->format_name($object['name'],$object['id']); ?>
	</td>
	<td class="cel_action">
	<?php echo Ajax::button('?page=localplay&action=delete_track&id=' . intval($object['id']),'delete',_('Delete'),'localplay_delete_' . intval($object['id'])); ?>
	</td>
</tr>
<?php } if (!count($object_ids)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="3"><span class="error"><?php echo _('No Records Found'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_track"><?php echo _('Track'); ?></th>
	<th class="cel_name"><?php echo _('Name'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
</table>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
