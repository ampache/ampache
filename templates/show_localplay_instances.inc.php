<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<?php show_box_top(_('Show Localplay Instances')); ?>
<table cellpadding="3" cellspacing="0" class="tabledata">
<tr>
	<?php foreach ($fields as $key=>$field) { ?>
		<th><?php echo $field['description']; ?></th>
	<?php } ?> 
	<th><?php echo _('Action'); ?></th>
</tr>
<?php foreach ($instances as $uid=>$name) { 
	$instance = $localplay->get_instance($uid);
?>
<tr class="<?php echo flip_class(); ?>" id="localplay_instance_<?php echo $uid; ?>">
	<?php foreach ($fields as $key=>$field) { ?>
	<td><?php echo $instance[$key]; ?></td>
	<?php } ?>
	<td>
		<a href="<?php echo Config::get('web_path'); ?>/localplay.php?action=edit_instance&instance=<?php echo $uid; ?>"><?php echo get_user_icon('edit',_('Edit Instance')); ?></a>
		<?php echo Ajax::button('?page=localplay&action=delete_instance&instance=' . $uid,'delete',_('Delete'),'delete_instance_' . $uid); ?>
	</td>
</tr>
<?php } ?>
</table>
<?php show_box_bottom(); ?>
