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
foreach ($controllers as $controller) { 
	$localplay = new Localplay($controller); 
	if (!$localplay->player_loaded()) { continue; } 
	$localplay->format(); 
	if (Localplay::is_enabled($controller)) { 
		$action 	= 'confirm_uninstall_localplay'; 
		$action_txt	= _('Disable'); 
	} 
	else { 
		$action = 'install_localplay';
		$action_txt	= _('Activate');
	} 
?>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo scrub_out($localplay->f_name); ?></td>
	<td><?php echo scrub_out($localplay->f_description); ?></td>
	<td><?php echo scrub_out($localplay->f_version); ?></td>
	<td><a href="<?php echo $web_path; ?>/admin/modules.php?action=<?php echo $action; ?>&amp;type=<?php echo urlencode($controller); ?>"><?php echo $action_txt; ?></a></td>
</tr>
<?php } if (!count($controllers)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="4"><span class="error"><?php echo _('No Records Found'); ?></span></td>
</tr>
<?php } ?>
</table>
<br />

