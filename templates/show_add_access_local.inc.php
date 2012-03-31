<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Add Access Local
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

?>
<?php show_box_top(T_('Add Local Network Definition'), 'box box_add_access_local'); ?>
<?php Error::display('general'); ?>
<form name="update_catalog" method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/admin/access.php?action=add_host&method=local">
<table class="tabledata" cellpadding="5" cellspacing="0">
<tr>
	<td><?php echo T_('Name'); ?>:</td>
	<td colspan="3">
		<input type="text" name="name" value="<?php echo scrub_out($_REQUEST['name']); ?>" size="20" />
	</td>
</tr>
<tr>
	<td><?php echo T_('Level'); ?>:</td>
	<td colspan="3">
		<input name="level" type="radio" value="5" /> <?php echo T_('View'); ?>
		<input name="level" type="radio" value="25" /> <?php echo T_('Read'); ?>
		<input name="level" type="radio" checked="checked" value="50" /> <?php echo T_('Read/Write'); ?>
		<input name="level" type="radio" value="75" /> <?php echo T_('All'); ?>
	</td>
</tr>
<tr>
	<td><?php echo T_('User'); ?>:</td>
	<td colspan="3">
		<?php show_user_select('user'); ?>
	</td>
</tr>

<tr>
	<td valign="top"><?php echo T_('Type'); ?>:</td>
	<td colspan="3">
		<input type="radio" name="addtype" value="network" /><?php echo T_('Local Network Definition'); ?><br />
		<input type="radio" name="addtype" value="streamnetwork" /><?php echo T_('Local Network Definition'); ?> + <?php echo T_('Stream Access'); ?> + <?php echo T_('Web Interface'); ?><br />
		<input type="radio" name="addtype" value="allnetwork" checked="checked" /><?php echo T_('Local Network Definition'); ?> + <?php echo T_('All'); ?><br />
	</td>
</tr>
<tr>
	<td colspan="4"><h4><?php echo T_('RPC Options'); ?></h4></td>
</tr>
<tr>
	<td><?php echo T_('Remote Key'); ?>:</td>
	<td colspan="3">
		<input type="text" name="key" value="<?php echo scrub_out($_REQUEST['end']); ?>" maxlength="32" />
	</td>
</tr>

<tr>
	<td colspan="4"><h3><?php echo T_('IPv4 or IPv6 Addresses'); ?></h3>
		<span class="information">(255.255.255.255) / (ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff)</span>
	</td>
</tr>
<tr>
	<td><?php echo T_('Start'); ?>:</td>
	<td>
		<?php Error::display('start'); ?>
		<input type="text" name="start" value="<?php echo scrub_out($_REQUEST['start']); ?>" size="20" />
	</td>
	<td><?php echo T_('End'); ?>:</td>
	<td>
		<?php Error::display('end'); ?>
		<input type="text" name="end" value="<?php echo scrub_out($_REQUEST['end']); ?>" size="20" />
	</td>
</tr>
</table>
<div class="formValidation">
		<?php echo Core::form_register('add_acl'); ?>
		<input class="button" type="submit" value="<?php echo T_('Create ACL'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
