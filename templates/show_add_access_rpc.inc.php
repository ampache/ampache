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
?>
<?php show_box_top(_('Add API / RPC Host')); ?>
<form name="update_catalog" method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/admin/access.php?action=add_host&method=rpc">
<table class="tabledata" cellpadding="5" cellspacing="0">
<tr>
	<td><?php echo _('Name'); ?>:</td>
	<td colspan="3">
		<input type="text" name="name" value="<?php echo scrub_out($_REQUEST['name']); ?>" size="20" />
	</td>
</tr>
<tr>
	<td><?php echo _('Level'); ?>:</td>
	<td colspan="3">
		<input name="level" type="radio" value="5" /> <?php echo _('View'); ?>
		<input name="level" type="radio" value="25" /> <?php echo _('Read'); ?>
		<input name="level" type="radio" checked="checked" value="50" /> <?php echo _('Read/Write'); ?>
		<input name="level" type="radio" value="75" /> <?php echo _('All'); ?>
	</td>
</tr>
<tr>
	<td><?php echo _('User'); ?>:</td>
	<td colspan="3">
		<?php show_user_select('user'); ?>
	</td>
</tr>

<tr>
	<td valign="top"><?php echo _('Type'); ?>:</td>
	<td colspan="3">
		<input type="radio" name="addtype" value="rpc" /><?php echo _('RPC'); ?><br />
		<input type="radio" name="addtype" value="streamrpc" checked="checked" /><?php echo _('RPC'); ?> + <?php echo _('Stream Access'); ?><br />
		<input type="radio" name="addtype" value="allrpc" /><?php echo _('RPC'); ?> + <?php echo _('All'); ?>
	</td>
</tr>
<tr>
	<td colspan="4"><h4><?php echo _('RPC Options'); ?></h4></td>
</tr>
<tr>
	<td><?php echo _('Remote Key'); ?>:</td>
	<td colspan="3">
		<input type="text" name="key" value="<?php echo scrub_out($_REQUEST['key']); ?>" maxlength="32" />
	</td>
</tr>

<tr>
	<td colspan="4"><h3><?php echo _('IPv4 or IPv6 Addresses'); ?></h3>
		<span class="information">(255.255.255.255) / (ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff)</span>
	</td>
</tr>
<tr>
	<td><?php echo _('Start'); ?>:</td>
	<td>
		<?php Error::display('start'); ?>
		<input type="text" name="start" value="<?php echo scrub_out($_REQUEST['start']); ?>" size="20" />
	</td>
	<td><?php echo _('End'); ?>:</td>
	<td>
		<?php Error::display('end'); ?>
		<input type="text" name="end" value="<?php echo scrub_out($_REQUEST['end']); ?>" size="20" />
	</td>
</tr>
</table>
<div class="formValidation">
		<?php echo Core::form_register('add_acl'); ?>
		<input class="button" type="submit" value="<?php echo _('Create ACL'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
