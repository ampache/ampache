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
?>

<div class="header1">
	<?php echo _("Editing"); ?> <?php echo $fullname; ?> <?php echo _("preferences"); ?> 
	<?php if ($user->has_access(100)) { ?>
	[<a href="<?php echo conf('web_path'); ?>/admin/preferences.php?action=fix_preferences&amp;user_id=<?php echo $user_id; ?>"><?php echo _("Rebuild Preferences"); ?></a>]
	<? } ?>
</div>
<form method="post" name="preferences" action="<?php echo conf('web_path'); ?><?php echo $target; ?>" enctype="multipart/form-data">
<table class="border" border="0" cellpadding="0" cellspacing="0">
<tr class="table-header">
	<th><?php echo _("Preference"); ?></th>
	<th><?php echo _("Value"); ?></th>
	<?php if ($user->has_access(100) AND $user_id == '0') { ?>
		<th><?php echo _("Type"); ?></th>
		<th><?php echo _("Apply to All"); ?></th>
	<?php } ?>
</tr>
<?php 

	foreach ($preferences as $pref) { 
		if ($pref->type == "system") { 
			$table_break = 1;
		}
		if ($pref->type == "user" AND $table_break == '1') { 
			$table_break = 0;
		?>
		<tr class="table-header">
		        <td colspan="4" style="background:<?php echo conf('bg_color1'); ?>;">&nbsp;</td>
		</tr>
		<?php
		} 
?>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _($pref->description); ?></td>
       <td><table>
               <tr>
                       <td><?php create_preference_input($pref->name,$pref->value); ?></td>
                       <?php if(preg_match('/Color/',$pref->description)) { ?>
			       <td><table width="40" height="20" border="3" bgcolor="<?php echo $pref->value;?>"><tr><td></td></tr></table></td>
                       <?php } else { ?>
                               <td></td>
                               <?php } ?>
               </tr>
               </table>
       </td>
 
	<?php if ($user->has_access(100) AND $user_id == '0') { ?>
		<td><?php echo $pref->type; ?></td>
		<td align="center"><input type="checkbox" name="check_<?php echo $pref->name; ?>" value="1" /></td>
	<?php } ?>
</tr>
<?php } ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="4">
		<input class="button" type="submit" value="<?php echo _("Update Preferences"); ?>" />
		<input type="hidden" name="action" value="update_preferences" />
		<input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
		&nbsp;&nbsp;
		<input class="button" type="submit" name="action" value="<?php echo _("Cancel"); ?>" />
	</td>
</tr>
</table>
</form>
<br /><br />
