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
if (($GLOBALS['user']->has_access(100) OR !Config::get('use_auth')) AND $_REQUEST['action'] == 'admin') { 
	$is_admin = true; 
}
?>
<table class="tabledata" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr class="odd">
	<th colspan="5" class="header2" align="left"><?php echo $preferences['title']; ?></th>
</tr>
<tr class="table-header">
	<th><?php echo _('Preference'); ?></th>
	<th><?php echo _('Value'); ?></th>
	<?php if ($is_admin) { ?>
	<th><?php echo _('Apply to All'); ?></th>
	<th><?php echo _('Access Level'); ?></th>
	<?php } ?>
</tr>
<?php foreach ($preferences['prefs'] as $pref) { ?>
	<tr class="<?php echo flip_class(); ?>">
		<td><?php echo _($pref['description']); ?></td>
		<td>
			<?php create_preference_input($pref['name'], $pref['value']); ?>
		</td>
		<?php if ($is_admin) { ?>
			<td align="center"><input type="checkbox" name="check_<?php echo $pref['name']; ?>" value="1" /></td>
			<td align="center">

			</td>
		<?php } ?>
	</tr>
<?php } // End foreach ($preferences['prefs'] as $pref) ?>
</table>
