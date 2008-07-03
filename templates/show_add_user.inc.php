<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<?php show_box_top(_('Adding a New User')); ?>
<?php Error::display('general'); ?>
<form name="add_user" enctype="multpart/form-data" method="post" action="<?php echo Config::get('web_path') . "/admin/users.php?action=add_user"; ?>">
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
	<td>
		<?php echo  _('Username'); ?>:
	</td>
	<td>
		<input type="text" name="username" size="30" maxlength="128" value="<?php echo scrub_out($_POST['username']); ?>" />
		<?php Error::display('username'); ?>
	</td>
</tr>
<tr>
	<td><?php echo  _('Full Name'); ?>:</td>
	<td>
		<input type="text" name="fullname" size="30" value="<?php echo scrub_out($_POST['fullname']); ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo  _('E-mail'); ?>:
	</td>
	<td>
		<input type="text" name="email" size="30" value="<?php echo scrub_out($_POST['email']); ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo  _('Password'); ?> :
	</td>
	<td>
		<input type="password" name="password_1" size="30" value="" />
		<?php Error::display('password'); ?>
	</td>
</tr>
<tr>
	<td>
		<?php echo  _('Confirm Password'); ?>:
	</td>
	<td>
		<input type="password" name="password_2" size="30" value="" />
	</td>
</tr>
<tr>
	<td>
		<?php echo  _('User Access Level'); ?>:
	</td>
        <td>
                <?php $var_name = "on_" . $client->access; ${$var_name} = 'selected="selected"'; ?>
                <select name="access">
                <option value="5" <?php echo $on_5; ?>><?php echo _('Guest'); ?></option>
                <option value="25" <?php echo $on_25; ?>><?php echo _('User'); ?></option>
		<option value="50" <?php echo $on_50; ?>><?php echo _('Content Manager'); ?></option>
		<option value="75" <?php echo $on_75; ?>><?php echo _('Catalog Manager'); ?></option>
                <option value="100" <?php echo $on_100; ?>><?php echo _('Admin'); ?></option>
                </select>
        </td>
</tr>
</table>
<div class="formValidation">
	<?php echo Core::form_register('add_user'); ?>
	<input type="submit" value="<?php echo _('Add User'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
