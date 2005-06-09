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
?>

<form name="update_user" method="post" action="<?php echo conf('web_path'); ?>/register.php" enctype="multipart/form-data">
<table class="tabledata" cellspacing="0" cellpadding="0" border="0" width="90%">
<tr>
	<td>
		<?php echo _("Username"); ?>:
	</td>
	<td>
		<input type="textbox" name="username" value="<?php echo $_REQUEST['username']; ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo _("Full Name"); ?>:
	</td>
	<td>
		<input type="textbox" name="fullname" size="30" value="<?php echo $_REQUEST['fullname']; ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo _("E-mail"); ?>:
	</td>
	<td>
		<input type="textbox" name="email" size="30" value="<?php echo $_REQUEST['email']; ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo _("Password"); ?> :
	</td>
	<td>
		<input type="password" name="password_1" size="30" value="" />
	</td>
</tr>
<tr>
	<td>
		<?php echo _("Confirm Password"); ?>:
	</td>
	<td>
		<input type="password" name="password_2" size="30" value="" />
	</td>
</tr>
<tr>
	<td colspan="2">
		<input type="hidden" name="action" value="add_user" />
		<input type="submit" value="<?php echo _("Register User"); ?>" />
	</td>
</tr>
</table>
</form>
