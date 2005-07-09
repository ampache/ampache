<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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

if ($type === 'new_user') { 
	$userfield = "<input type=\"text\" name=\"new_username\" size=\"30\" value=\"$username\" />";
	$title = _("Adding a New User");
}
else {
	$userfield = "$username";
	$title = _("Editing existing User");
}
?>

<br />
<div class="header2"><?php echo $title; ?></div>
<?php $GLOBALS['error']->print_error('general'); ?>
<form name="update_user" method="post" action="<?php echo conf('web_path') . "/admin/users.php"; ?>">
<table class="text-box" cellspacing="0" cellpadding="0" border="0">
<tr>
	<td>
		<?php echo  _("Username"); ; ?>:
	</td>
	<td>
		<?php echo $userfield; ?>
		<?php $GLOBALS['error']->print_error('username'); ?>
	</td>
</tr>
<tr>
	<td><?php echo  _("Full Name"); ; ?>:</td>
 	<td>
		<input type="text" name="new_fullname" size="30" value="<?php echo $fullname; ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo  _("E-mail"); ?>:
	</td>
	<td>
		<input type="text" name="new_email" size="30" value="<?php echo $email; ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo  _("Password"); ?> :
	</td>
	<td>
		<input type="password" name="new_password_1" size="30" value="" />
		<?php $GLOBALS['error']->print_error('password'); ?>
	</td>
</tr>
<tr>
	<td>
		<?php echo  _("Confirm Password"); ?>:
	</td>
	<td>
		<input type="password" name="new_password_2" size="30" value="" />
	</td>
</tr>
<tr>
	<td>
		<?php echo  _("User Access Level"); ; ?>:
	</td>
	<td>
		<select name="user_access">
	    	<option value="1" <?php  if($access==='1') echo "selected=\"selected\""; ?>>Guest</option>
	        <option value="user" <?php  if($access==='user') echo "selected=\"selected\""; ?>>User</option>
	        <option value="admin" <?php  if($access==='admin') echo "selected=\"selected\""; ?>>Admin</option>
	        </select>
	</td>
</tr>
</table>
<?php

    if ( $type == 'new_user' ) 
    {
    	echo "<input type=\"hidden\" name=\"action\" value=\"add_user\" />";
        echo "<input type=\"submit\" value=\"" . _("Add User") . "\" />";
    }
    else 
    {
    	echo "<input type=\"hidden\" name=\"action\" value=\"update_user\" />\n";
        echo "<input type=\"submit\" value=\"" . _("Update User") . "\" />\n";
        echo "<input type=\"hidden\" name=\"new_username\" value=\"$username\" />";
    }

?>
</form>
