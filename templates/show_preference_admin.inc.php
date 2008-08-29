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
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_preference" />
  <col id="col_level" />
</colgroup>
<tr class="th-top">
	<th class="cel_preference"><?php echo _('Preference'); ?></th>
	<th class="cel_level"><?php echo _('Level'); ?></th>
</tr>
<?php foreach ($preferences as $preference) { 
	unset($is_25,$is_5,$is_100);
?>
<tr class="<?php echo flip_class(); ?>">
	<td class="cel_preference"><?php echo scrub_out(_($preference['description'])); ?></td>
	<td class="cel_level">
		<?php $level_name = "is_" . $preference['level']; ${$level_name} = 'selected="selected"'; ?> 
		<select name="prefs[<?php echo scrub_out($preference['name']); ?>]">
			<option value="5" <?php echo $is_5; ?>><?php echo _('Guest'); ?></option>
			<option value="25" <?php echo $is_25; ?>><?php echo _('User'); ?></option>
			<option value="100" <?php echo $is_100; ?>><?php echo _('Admin'); ?></option>
		</select>
	</td>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_preference"><?php echo _('Preference'); ?></th>
	<th class="cel_level"><?php echo _('Level'); ?></th>
</tr>
</table>
<div class="formValidation">
		<input type="hidden" name="action" value="set_preferences" />
		<input type="submit" value="<?php echo _('Update'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
