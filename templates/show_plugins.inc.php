<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
$web_path = Config::get('web_path'); 
?>
<!-- Plugin we've found --> 
<table class="tabledata" border="0" cellspacing="0">
<tr class="table-header">
	<th><?php echo _('Name'); ?></th>
	<th><?php echo _('Description'); ?></th>
	<th><?php echo _('Version'); ?></th>
	<th><?php echo _('Action'); ?></th>
</tr>
<?php 
foreach ($plugins as $plugin_name) { 
	$plugin = new Plugin($plugin_name); 
        if (!Plugin::is_installed($plugin_name)) {
                $action = "<a href=\"" . $web_path . "/admin/modules.php?action=install_plugin&amp;plugin=" . scrub_out($plugin_name) . "\">" .
                        _('Activate') . "</a>";
        }
        else {
                $action = "<a href=\"" . $web_path . "/admin/modules.php?action=confirm_uninstall_plugin&amp;plugin=" . scrub_out($plugin_name) . "\">" .
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
<br />

