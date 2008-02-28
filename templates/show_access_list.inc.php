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

/*!
	@header show access list
	@discussion default display for access admin page

*/
$web_path = Config::get('web_path');
?>
<?php show_box_top(_('Ampache Access Control')); ?>
<p>Since your catalog can be accessed remotely you may want to limit the access from
remote sources so you are not in violation of copyright laws.  By default your
server will allow anyone with an account to stream music. It will not allow any
other Ampache servers to connect to it to share catalog information.  Use tool below 
to add any server's IP address that you want to access your Ampache catalog or be able to 
stream from this server.</p>

<p>
<a class="button" href="<?php echo $web_path; ?>/admin/access.php?action=show_add_host"><?php echo _('Add Entry'); ?></a>
</p>
<?php if (count($list)) { ?>
<table cellspacing="1" cellpadding="3" class="tabledata">
<tr class="table-data">
	<th><?php echo _('Name'); ?></th>
	<th><?php echo _('Start Address'); ?></th>
	<th><?php echo _('End Address'); ?></th>
	<th><?php echo _('Level'); ?></th>
	<th><?php echo _('User'); ?></th>
	<th><?php echo _('Key'); ?></th>
	<th><?php echo _('Type'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php 
	/* Start foreach List Item */
	foreach ($list as $access_id) { 
		$access = new Access($access_id); 
?>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo scrub_out($access->name); ?></td>
	<td><?php echo long2ip($access->start); ?></td>
	<td><?php echo long2ip($access->end); ?></td>
	<td><?php echo $access->get_level_name(); ?></td>
	<td><?php echo $access->get_user_name(); ?></td>
	<td><?php echo $access->key; ?></td>
	<td><?php echo $access->get_type_name(); ?></td>
	<td>
		<a href="<?php echo $web_path; ?>/admin/access.php?action=show_edit_record&amp;access_id=<?php echo scrub_out($access->id); ?>"><?php echo get_user_icon('edit'); ?></a>
		<a href="<?php echo $web_path; ?>/admin/access.php?action=delete_record&amp;access_id=<?php echo scrub_out($access->id); ?>"><?php echo get_user_icon('delete'); ?></a>
	</td>
</tr>
	<?php  } // end foreach ?>
</table>
<?php  } // end if count ?>
<?php show_box_bottom(); ?>

