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
<?php show_box_top(_('Edit Access Control List')); ?>
<form name="edit_access" method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/admin/access.php?action=update_record&access_id=<?php echo intval($access->id); ?>">
<table class="table-data">
<tr>
	<td><?php echo _('Name'); ?>: </td>
	<td colspan="3"><input type="text" name="name" value="<?php echo scrub_out($access->name); ?>" /></td>
</tr>
<tr>
	<td><?php echo _('ACL Type'); ?>: </td>
        <td colspan="3">
                <select name="type">
			<?php $name = 'sl_' . $access->type; ${$name} = ' selected="selected"'; ?>
                        <option value="stream"<?php echo $sl_stream; ?>><?php echo _('Stream Access'); ?></option>
                        <option value="interface"<?php echo $sl_interface; ?>><?php echo _('Web Interface'); ?></option>
                        <option value="network"<?php echo $sl_network; ?>><?php echo _('Local Network Definition'); ?></option>
                        <option value="rpc"<?php echo $sl_rpc; ?>><?php echo _('RPC'); ?></option>
                </select>
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
		<input type="text" name="start" value="<?php echo $access->f_start; ?>" size="20" />
	</td>
	<td><?php echo _('End'); ?>:</td>
	<td>
		<?php Error::display('end'); ?>
		<input type="text" name="end" value="<?php echo $access->f_end; ?>" size="20" />
	</td>
</tr>
<tr>
	<td><?php echo _('User'); ?>:</td>
	<td colspan="3">
		<?php show_user_select('user',$access->user); ?>
	</td>
</tr>
<tr>
	<td><?php echo _('Remote Key'); ?></td>
	<td colspan="3">
		<input type="text" name="key" value="<?php echo scrub_out($access->key); ?>" size="32" maxlength="32" />
	</td>
</tr>
<tr>
	<td><?php echo _('Level'); ?>:</td>
	<td colspan="3">
		<?php $name = 'level_' . $access->level; ${$name} = 'checked="checked"'; ?>
		<input type="radio" name="level" value="5"  <?php echo $level_5;  ?>><?php echo _('View'); ?>
		<input type="radio" name="level" value="25" <?php echo $level_25; ?>><?php echo _('Read'); ?>
		<input type="radio" name="level" value="50" <?php echo $level_50; ?>><?php echo _('Read/Write'); ?>
		<input type="radio" name="level" value="75" <?php echo $level_75; ?>><?php echo _('All'); ?>
	</td>
</tr>
</table>
<div class="formValidation">
	<?php echo Core::form_register('edit_acl'); ?>
	<input type="submit" value="<?php echo _('Update'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
