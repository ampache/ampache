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
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_name" />
  <col id="col_description" />
  <col id="col_version" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
	<th class="cel_name"><?php echo _('Name'); ?></th>
	<th class="cel_description"><?php echo _('Description'); ?></th>
	<th class="cel_version"><?php echo _('Version'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
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
	<td class="cel_name"><?php echo scrub_out($localplay->f_name); ?></td>
	<td class="cel_description"><?php echo scrub_out($localplay->f_description); ?></td>
	<td class="cel_version"><?php echo scrub_out($localplay->f_version); ?></td>
	<td class="cel_action"><a href="<?php echo $web_path; ?>/admin/modules.php?action=<?php echo $action; ?>&amp;type=<?php echo urlencode($controller); ?>"><?php echo $action_txt; ?></a></td>
</tr>
<?php } if (!count($controllers)) { ?>
<tr class="<?php echo flip_class(); ?>">
	<td colspan="4"><span class="error"><?php echo _('No Records Found'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
	<th class="cel_name"><?php echo _('Name'); ?></th>
	<th class="cel_description"><?php echo _('Description'); ?></th>
	<th class="cel_version"><?php echo _('Version'); ?></th>
	<th class="cel_action"><?php echo _('Action'); ?></th>
</tr>
</table>
<br />

