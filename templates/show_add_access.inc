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
?>
<?php show_box_top(_('Add Access for a Host')); ?>
<p><?php echo _('Use the form below to add a host that you want to have access to your Ampache catalog.'); ?></p>


<form name="update_catalog" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/admin/access.php">
<table cellpadding="5" cellspacing="0" border="0">
<tr>
	<td><?php echo _('Name'); ?>:</td>
	<td>
		<input type="text" name="name" value="<?php echo scrub_out($_REQUEST['name']); ?>" size="20" />
	</td>
</tr>
<tr>
	<td><?php echo _('Start IP Address'); ?>:</td>
	<td>
		<input type="text" name="start" value="<?php echo scrub_out($_REQUEST['start']); ?>" size="20" maxlength="15" />
	</td>
</tr>
<tr>
	<td><?php echo _('End IP Address'); ?>:</td>
	<td>
		<input type="text" name="end" value="<?php echo scrub_out($_REQUEST['end']); ?>" size="20" maxlength="15" />
	</td>
</tr>
<tr>
	<td><?php echo _('User'); ?>:</td>
	<td>
		<?php show_user_select('user'); ?>
	</td>
</tr>
<tr>
	<td><?php echo _('Level'); ?>:</td>
	<td>
		<select name="level">
			<option selected="selected" value="5" ><?php echo _('View'); ?></option>
			<option value="25"><?php echo _('Read'); ?></option>
			<option value="50"><?php echo _('Read/Write'); ?></option>
			<option value="75"><?php echo _('All'); ?></option>
		</select>
	</td>
</tr>
<tr>
	<td><?php echo _('ACL Type'); ?>:</td>
	<td>
		<select name="type">
			<option selected="selected" value="stream"><?php echo _('Stream Access'); ?></option>
			<option value="interface"><?php echo _('Web Interface'); ?></option>
			<option value="network"><?php echo _('Local Network Definition'); ?></option>
			<option value="xml-rpc"><?php echo _('XML-RPC'); ?></option>
		</select>
	</td>
</tr>
<tr>
	<td colspan="2"><br /><?php echo _('XML-RPC Options'); ?>:</td>
</tr>
<tr>
	<td><?php echo _('Remote Key'); ?>:</td>
	<td>
		<input type="text" name="key" value="<?php echo scrub_out($_REQUEST['end']); ?>" size="32" maxlength="32" />
	</td>
</tr>
<tr>
	<td colspan="2">
		<br />
		<input type="hidden" name="action" value="add_host" />
		<input class="button" type="submit" value="<?php echo _('Create ACL'); ?>" />
	</td>
</tr>
</table>
</form>
<?php show_box_bottom(); ?>
