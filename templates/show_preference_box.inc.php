<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
	@discussion shows a single preference box

*/

/* I'm cheating a little here, check to see if we want to show the
 * Apply to All button on this page 
 */
if ($GLOBALS['user']->has_access(100) AND conf('use_auth')) { 
	$show_apply_to_all = true;
}
?>


<table class="border" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr class="odd">
	<th colspan="3" class="header2" align="left"><?php echo $preferences['title']; ?></th>
</tr>
<tr class="table-header">
	<th><?php echo _('Preference'); ?></th>
	<th><?php echo _('Value'); ?></th>
	<?php if ($show_apply_to_all) { ?>
		<th><?php echo _('Apply to All'); ?></th>
	<?php } ?>
</tr>
<?php 
foreach ($preferences['prefs'] as $pref) { ?>
	<tr class="<?php echo flip_class(); ?>">
		<td><?php echo _($pref['description']); ?></td>
		<td>
			<table>
			<tr>
				<td><?php create_preference_input($pref['name'], $pref['value']); ?></td>
				<?php if (preg_match('/Color/', $pref['description'])) { ?>
					<td>
						<table width="40" border="3" style="border-collapse: collapse;" bgcolor="<?php echo $pref['value'];?>">
						<tr style="height:20px;">
							<td></td>
						</tr>
						</table>
					</td>
				<?php } else { ?>
					<td></td>
					<?php } ?>
			</tr>
			</table>
		</td>
		<?php if ($show_apply_to_all) { ?>
			<td align="center"><input type="checkbox" name="check_<?php echo $pref['name']; ?>" value="1" /></td>
		<?php } ?>
	</tr>
<?php } // End foreach ($preferences['prefs'] as $pref) ?>
</table>
