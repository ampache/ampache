<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
<?php $GLOBALS['error']->print_error('general'); ?>
<form name="add_user" enctype="multpart/form-data" method="post" action="<?php echo conf('web_path') . "/admin/users.php"; ?>">
<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
<tr>
	<td>
		<?php echo  _('Username'); ?>:
	</td>
	<td>
		<input type="text" name="username" size="30" maxlength="128" value="<?php echo scrub_out($_POST['username']); ?>" />
		<?php $GLOBALS['error']->print_error('username'); ?>
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
		<?php $GLOBALS['error']->print_error('password'); ?>
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
                <?php $var_name = "on_" . $working_user->access; ${$var_name} = 'selected="selected"'; ?>
                <select name="access">
                <option value="5" <?php echo $on_5; ?>><?php echo _('Guest'); ?></option>
                <option value="25" <?php echo $on_25; ?>><?php echo _('User'); ?></option>
                <option value="100" <?php echo $on_100; ?>><?php echo _('Admin'); ?></option>
                </select>
        </td>
</tr>
	<td colspan="2">
		<input type="submit" value="<?php echo _('Add User'); ?>" />
		<input type="hidden" name="action" value="add_user" />
	</td>
</tr>
</table>
</form>
<?php show_box_bottom(); ?>
