<?php
/*

 Copyright (c) Ampache.org
 All Rights Reserved

 this program is free software; you can redistribute it and/or
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
?>
<?php show_box_top(_('Debug Tools')); ?>
<ul>
	<li><a href="<?php echo Config::get('web_path'); ?>/system.php?action=generate_config"><?php echo _('Generate Configuration'); ?></a></li>
	<li><a href="<?php echo Config::get('web_path'); ?>/system.php?action=check_php_settings"><?php echo _('Check PHP Settings'); ?></a></li>
	<li><a href="<?php echo Config::get('web_path'); ?>/system.php?action=check_iconv"><?php echo _('Check Iconv'); ?></a></li>
</ul>
<?php show_box_bottom(); ?>

<?php show_box_top(_('Current Configuration')); ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
   <col id="col_configuration">
   <col id="col_value">
</colgroup>
<tr class="th-top">
	<th class="cel_configuration"><?php echo _('Preference'); ?></th>
	<th class="cel_value"><?php echo _('Value'); ?></th>
</tr>
<?php foreach ($configuration as $key=>$value) { 
	if ($key == 'database_password' || $key == 'mysql_password') { $value = '*********'; } 
	if (is_array($value)) { 
		$string = ''; 
		foreach ($value as $setting) { 
			$string .= $setting . '<br />'; 
		} 
		$value = $string; 
	} 
?>
<tr class="<?php echo flip_class(); ?>">
	<td valign="top"><strong><?php echo $key; ?></strong></td>
	<td><?php echo $value; ?></td>
</tr>
<?php } ?>
</table>
<?php show_box_bottom(); ?>
