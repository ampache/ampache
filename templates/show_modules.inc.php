<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/**
 * for now we only have the localplay modules so this is going to be centered on them
 * however the idea would be as module support is added more and more are put on this
 * same page 
 */

/* Get Localplay Modules */
$localplay_modules = get_localplay_controllers(); 
$web_path = conf('web_path'); 
?>
<span class="header2"><?php echo _('Modules'); ?></span>
<table class="border" border="0" cellspacing="0">
<tr class="table-header">
	<th><?php echo _('Module Name'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php 
foreach ($localplay_modules as $module) { 
	if (!verify_localplay_preferences($module)) { 
		$action = "<a href=\"" . $web_path . "/admin/modules.php?action=insert_localplay_preferences&amp;type=" . $module . "\">" . 
			_('Activate') . "</a>";
	}
	else { 
		$action = "<a href=\"" . $web_path . "/admin/modules.php?action=confirm_remove_localplay_preferences&amp;type=" . $module . "\">" . 
			_('Deactivate') . "</a>";
	}
?>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo scrub_out($module); ?></td>
	<td><?php echo $action; ?></td>
</tr>
<?php } if (!count($localplay_modules)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="2"><span class="error"><?php echo _('No Records Found'); ?></span></td>
</tr>
<?php } ?>
</table>
