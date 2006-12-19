<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

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

/**
 * for now we only have the localplay modules so this is going to be centered on them
 * however the idea would be as module support is added more and more are put on this
 * same page 
 */

/* Get Localplay Modules */
$localplay_modules 	= get_localplay_controllers('disabled'); 
/* Get Plugins */
$plugins		= get_plugins(); 
$web_path = conf('web_path'); 
?>

<!-- Localplay Modules --> 
<table class="tabledata" border="0" cellspacing="0">
<tr class="odd">
<th colspan="2" class="header2" align="left"><?php echo _('Localplay Modules');?></th>
</tr>
<tr class="table-header">
	<td><?php echo _('Module Name'); ?></td>
	<td><?php echo _('Action'); ?></td>
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
<br />

<!-- Plugins --> 
<table class="tabledata">
<tr class="odd">
<th colspan="4" class="header2" align="left"><?php echo _('Available Plugins'); ?></th>
<tr class="table-header">
	<td><?php echo _('Name'); ?></td>
	<td><?php echo _('Description'); ?></td>
	<td><?php echo _('Version'); ?></td>
	<td><?php echo _('Action'); ?></td>
</tr>
<?php 
foreach ($plugins as $key=>$plugin) { 
        if (!$plugin->is_installed()) {
                $action = "<a href=\"" . $web_path . "/admin/modules.php?action=install_plugin&amp;plugin=" . scrub_out($key) . "\">" .
                        _('Activate') . "</a>";
        }
        else {
                $action = "<a href=\"" . $web_path . "/admin/modules.php?action=confirm_uninstall_plugin&amp;plugin=" . scrub_out($key) . "\">" .
                        _('Deactivate') . "</a>";
        }
?>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo scrub_out($plugin->_plugin->name); ?></td>
	<td><?php echo scrub_out($plugin->_plugin->description); ?></td>
	<td><?php echo scrub_out($plugin->_plugin->version); ?></td>
	<td><?php echo $action; ?></td>
</tr>
<?php } if (!count($plugins)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="4"><span class="error"><?php echo _('No Records Found'); ?></span></td>
</tr>
<?php } ?>
</table>

