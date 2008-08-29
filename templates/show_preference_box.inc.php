<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved

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

/* I'm cheating a little here, check to see if we want to show the
 * Apply to All button on this page 
 */
if ((Access::check('interface','100') OR !Config::get('use_auth')) AND $_REQUEST['action'] == 'admin') { 
	$is_admin = true; 
}
?>
<h4><?php echo _($preferences['title']); ?></h4>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_preference" />
  <col id="col_value" />
	<?php if ($is_admin) { ?>
  <col id="col_applytoall" />
  <col id="col_level" />
	<?php } ?>
</colgroup>
<tr class="th-top">
	<th class="cel_preference"><?php echo _('Preference'); ?></th>
	<th class="cel_value"><?php echo _('Value'); ?></th>
	<?php if ($is_admin) { ?>
	<th class="cel_applytoall"><?php echo _('Apply to All'); ?></th>
	<th class="cel_level"><?php echo _('Access Level'); ?></th>
	<?php } ?>
</tr>
<?php foreach ($preferences['prefs'] as $pref) { ?>
	<tr class="<?php echo flip_class(); ?>">
		<td class="cel_preference"><?php echo _($pref['description']); ?></td>
		<td class="cel_value">
			<?php create_preference_input($pref['name'], $pref['value']); ?>
		</td>
		<?php if ($is_admin) { ?>
			<td class="cel_applytoall"><input type="checkbox" name="check_<?php echo $pref['name']; ?>" value="1" /></td>
			<td class="cel_level">
				<?php $name = 'on_' . $pref['level']; ${$name} = 'selected="selected"';  ?> 
				<select name="level_<?php echo $pref['name']; ?>">
					<option value="5" <?php echo $on_5; ?>><?php echo _('Guest'); ?></option> 
					<option value="25" <?php echo $on_25; ?>><?php echo _('User'); ?></option>
					<option value="50" <?php echo $on_50; ?>><?php echo _('Content Manager'); ?></option>
					<option value="75" <?php echo $on_75; ?>><?php echo _('Catalog Manager'); ?></option>
					<option value="100" <?php echo $on_100; ?>><?php echo _('Admin'); ?></option>
				</select> 
				<?php unset(${$name}); ?>
			</td>
		<?php } ?>
	</tr>
<?php } // End foreach ($preferences['prefs'] as $pref) ?>
<tr class="th-bottom">
	<th class="cel_preference"><?php echo _('Preference'); ?></th>
	<th class="cel_value"><?php echo _('Value'); ?></th>
	<?php if ($is_admin) { ?>
	<th class="cel_applytoall"><?php echo _('Apply to All'); ?></th>
	<th class="cel_level"><?php echo _('Access Level'); ?></th>
	<?php } ?>
</tr>
</table>
