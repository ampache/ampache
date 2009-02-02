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
<div id="information_actions">
<ul>
<li>
	<a href="<?php echo Config::get('web_path'); ?>/admin/system.php?action=generate_config"><?php echo get_user_icon('cog'); ?></a>
	<?php echo _('Generate Configuration'); ?>
</li>
<li>
	<a href="<?php echo Config::get('web_path'); ?>/admin/system.php?action=reset_db_charset"><?php echo get_user_icon('server_lightning'); ?></a>
	<?php echo _('Set Database Charset'); ?>
</li>
</ul>
</div>
<?php show_box_bottom(); ?>
<?php show_box_top(_('PHP Settings')); ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
	<col id="col_php_setting">
	<col id="col_php_value">
</colgroup>
<tr class="th-top">
	<th class="cel_php_setting"><?php echo _('Setting'); ?></th>
	<th class="cel_php_value"><?php echo _('Value'); ?></th>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Memory Limit'); ?></td>
	<td><?php echo ini_get('memory_limit'); ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Maximum Execution Time'); ?></td>
	<td><?php echo ini_get('max_execution_time'); ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Override Execution Time'); ?></td>
	<td><?php set_time_limit(0); echo ini_get('max_execution_time') ? _('Failed') : _('Succeeded'); ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Safe Mode'); ?></td>
	<td><?php echo print_boolean(ini_get('safe_mode')); ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td>Open Basedir</td>
	<td><?php echo ini_get('open_basedir'); ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Zlib Support'); ?></td>
	<td><?php echo print_boolean(function_exists('gzcompress')); ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('GD Support'); ?></td>
	<td><?php echo print_boolean(function_exists('ImageCreateFromString')); ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Iconv Support'); ?></td>
	<td><?php echo print_boolean(function_exists('iconv')); ?></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _('Gettext Support'); ?></td>
	<td><?php echo print_boolean(function_exists('bindtextdomain')); ?></td>
</tr>
</table>
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
	if (Preference::is_boolean($key)) { 
		$value = print_boolean($value); 
	} 
?>
<tr class="<?php echo flip_class(); ?>">
	<td valign="top"><strong><?php echo $key; ?></strong></td>
	<td><?php echo $value; ?></td>
</tr>
<?php } ?>
</table>
<?php show_box_bottom(); ?>
