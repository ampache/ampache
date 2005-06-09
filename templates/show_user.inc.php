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
<br /><br />
<div class="text-box">
<form name="change_password" method="post" action="<?php echo conf('web_path'); ?>/user.php?action=update_user" enctype="multipart/form-data" >
<p class="header2">Changing User Information for <?php echo $this_user->fullname; ?></p>
<table>

<tr>
        <td>
		<?php echo _("Name"); ?>:
	</td>
        <td>
		<input type="text" name="fullname" size="30" value="<?php echo $this_user->fullname; ?>" />
	</td>
        </tr>

<tr>
	<td>
		<?php echo _("E-mail"); ?>:
	</td>
        <td>
		<input type="text" name="email" size="30" value="<?php echo $this_user->email; ?>" />
	</td>
</tr>
<tr>
        <td>
		<?php echo _("View Limit"); ?>:
	</td>
	<td>
		<input type="text" name="offset_limit" size="5" value="<?php echo $this_user->offset_limit; ?>" />
	</td>
</tr>
</table>
	<input type="hidden" name="user_id" value="<?php echo $this_user->username; ?>" />
	<input type="submit" name="action" value="<?php echo _("Update Profile"); ?>" />
</form>
</div>
<br />
<div class="text-box">
<form name="change_password" method="post" action="<?php echo conf('web_path'); ?>/user.php?action=change_password" enctype="multipart/form-data" >
<span class="header2">Changing User Password</span>
<?php $GLOBALS['error']->print_error('password'); ?>
<table border="0" cellpadding="5" cellspacing="0">
<tr>
        <td>
		<?php echo _("Enter password"); ?>:
	</td>
	<td>
		<input type="password" name="password" size="30" />
	</td>
</tr>
<tr>
        <td>
		<?php echo _("Confirm Password"); ?>:
	</td>
        <td>
		<input type="password" name="confirm_password" size="30" />	
	</td>
</tr>
</table>
		<input type="hidden" name="user_id" value="<?php echo $this_user->username; ?>" />
	        <input type="submit" name="action" value="<?php echo _("Change Password"); ?>" />
</form>
</div>
<br />
<div class="text-box">
<form name="clear_statistics" method="post" action="<?php echo conf('web_path'); ?>/user.php?action=clear_stats" enctype="multipart/form-data">
<span class="header2">Delete Your Personal Statistics</span><br />
<input type="hidden" name="user_id" value="<?php echo $this_user->username; ?>" />
<input type="submit" value="<?php echo _("Clear Stats"); ?>">
</form>
</div>
