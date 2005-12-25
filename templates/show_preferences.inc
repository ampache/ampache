<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
 All rights reserved

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

/*!
	@header Show Preferences
	@discussion shows edit page for preferences

*/

/* I'm cheating a little here, check to see if we want to show the
 * Apply to All button on this page 
 */
if ($GLOBALS['user']->has_access(100) AND $user_id == '-1' AND conf('use_auth')) { 
	$show_apply_to_all = true;
}

?>


<table class="text-box">
<tr><td>
<span class="header1">
	<?php echo _("Editing"); ?> <?php echo $fullname; ?> <?php echo _("preferences"); ?> 
	<?php if ($GLOBALS['user']->has_access(100)) { ?>
	[<a href="<?php echo conf('web_path'); ?>/admin/preferences.php?action=fix_preferences&amp;user_id=<?php echo $user_id; ?>"><?php echo _("Rebuild Preferences"); ?></a>]
	<?php } ?>
</span>


<form method="post" name="preferences" action="<?php echo conf('web_path'); ?><?php echo $target; ?>" enctype="multipart/form-data">
<table cellspacing="10">
<tr>
	<td valign="top"><?php show_preference_box($preferences['theme']); ?></td>
	<?php if (isset($preferences['system'])) { ?>
	<td valign="top"><?php show_preference_box($preferences['system']); ?></td>
	<?php } else { ?>
	<td valign="top">&nbsp;</td>
	<?php } ?>
</tr>
<tr>
	<td valign="top"><?php show_preference_box($preferences['streaming']); ?></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="top"><?php show_preference_box($preferences['interface']); ?></td>
	<td>&nbsp;</td>
</tr>
<tr>
	<td valign="top"><?php show_preference_box($preferences['options']); ?></td>
	<td>&nbsp;</td>
</tr>
</table>

	<input class="button" type="submit" value="<?php echo _("Update Preferences"); ?>" />
	<input type="hidden" name="action" value="update_preferences" />
	<input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
	&nbsp;&nbsp;
	<input class="button" type="submit" name="action" value="<?php echo _("Cancel"); ?>" />

	</form>
</td></tr>
</table>
