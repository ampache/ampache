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
$web_path = conf('web_path');
?>
<?php show_box_top(_('Host Access to Your Catalog')); ?>
<p>Since your catalog can be accessed remotely you may want to limit the access from
remote sources so you are not in violation of copyright laws.  By default your
server will allow anyone with an account to stream music. It will not allow any
other Ampache servers to connect to it to share catalog information.  Use tool below 
to add any server's IP address that you want to access your Ampache catalog or be able to 
stream from this server.</p>

<p>
<span class="text-action">
<a href="<?php echo $web_path; ?>/admin/access.php?action=show_add_host"><?php echo _('Add Entry'); ?></a>
</span>
</p>
<?php if (count($list)) { ?>
<table cellspacing="1" cellpadding="3" class="tabledata">
<tr class="table-header" align="center">
	<td><?php echo _('Name'); ?></td>
	<td><?php echo _('Start Address'); ?></td>
	<td><?php echo _('End Address'); ?></td>
	<td><?php echo _('Level'); ?></td>
	<td><?php echo _('User'); ?></td>
	<td><?php echo _('Key'); ?></td>
	<td><?php echo _('Type'); ?></td>
	<td><?php echo _('Action'); ?></td>
</tr>
<?php 
	/* Start foreach List Item */
	foreach ($list as $access) { 
?>
		<tr class="<?php echo flip_class(); ?>">
			<td><?php echo scrub_out($access->name); ?></td>
			<td><?php echo int2ip($access->start); ?></td>
			<td><?php echo int2ip($access->end); ?></td>
			<td><?php echo $access->get_level_name(); ?></td>
			<td><?php echo $access->get_user_name(); ?></td>
			<td><?php echo $access->key; ?></td>
			<td><?php echo $access->get_type_name(); ?></td>
			<td>
				<a href="<?php echo $web_path; ?>/admin/access.php?action=show_edit_host&amp;access_id=<?php echo scrub_out($access->id); ?>"><?php echo _('Edit'); ?></a>
				| 
				<a href="<?php echo $web_path; ?>/admin/access.php?action=show_confirm_delete&amp;access_id=<?php echo scrub_out($access->id); ?>"><?php print _("Revoke"); ?></a>
			</td>
		</tr>
	<?php  } // end foreach ?>
</table>
<?php  } // end if count ?>
<?php show_box_bottom(); ?>

