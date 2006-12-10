<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<?php show_box_top(_('Preference Administration')); ?>
<form method="post" action="<?php echo conf('web_path'); ?>/admin/preferences.php" enctype="multipart/form-data">
<table cellspacing="0">
<tr class="table-header">
	<td><?php echo _('Preference'); ?></td>
	<td><?php echo _('Level'); ?></td>
</tr>
<?php foreach ($preferences as $preference) { 
	unset($is_25,$is_5,$is_100);
?>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo scrub_out($preference['description']); ?></td>
	<td>
		<?php $level_name = "is_" . $preference['level']; ${$level_name} = 'selected="selected"'; ?> 
		<select name="prefs[<?php echo scrub_out($preference['name']); ?>]">
			<option value="5" <?php echo $is_5; ?>><?php echo _('Guest'); ?></option>
			<option value="25" <?php echo $is_25; ?>><?php echo _('User'); ?></option>
			<option value="100" <?php echo $is_100; ?>><?php echo _('Admin'); ?></option>
		</select>
	</td>
</tr>
<?php } ?>
<tr>
	<td colspan="2">
		<input type="hidden" name="action" value="set_preferences" />
		<input type="submit" value="<?php echo _('Update'); ?>" />
	</td>
</tr>	
</table>
</form>
<?php show_box_bottom(); ?>
