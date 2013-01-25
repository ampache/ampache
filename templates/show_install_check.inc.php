<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
?>
<?php if (!defined('INSTALL')) { exit; } ?>
<h4><?php echo T_('Required'); ?></h4>
<table border="0" cellspacing="0" cellpadding="3">
<tr>
<td><?php echo sprintf(T_("%s is readable"),"ampache.cfg.php.dist"); ?></td>
<td>
<?php
	if (!is_readable($prefix . '/config/ampache.cfg.php.dist')) {
		echo debug_result('',false);
		Error::add('install',sprintf(T_("%s is readable"),"ampache.cfg.php.dist"));
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr>
<tr>
<td><?php echo sprintf(T_('%s is readable'), 'ampache.sql'); ?></td>
<td>
<?php
	if (!is_readable($prefix . '/sql/ampache.sql')) {
		echo debug_result('', false);
		Error::add('install', sprintf(T_('%s is readable'), 'ampache.sql'));
	}
	else {
		echo debug_result('', true);
	}
?>
</td>
</tr>
<tr>
<td><?php echo T_('PHP Version'); ?>:</td>
<td>
<?php
	if(!check_php_ver()) {
		if (function_exists('hash_algos')) { $algos = hash_algos(); }
		if (strtoupper(substr(PHP_OS,0,3)) == 'WIN') {
			$version_string = phpversion() . " < PHP 5.3 ";
		}
		else {
			$version_string = phpversion() . " ";
		}
		$string = $version_string . T_('Hash Function Exists') . " " . print_bool(function_exists('hash_algos')) . " " . T_('SHA256 Support') . " " . print_bool(in_array('sha256',$algos));
		echo debug_result($string,false);
		Error::add('install', T_('PHP Version'));
	}
	else {
		echo debug_result(phpversion(),true);
	}
?>
</td>
</tr><tr>
<td><?php echo T_('PHP MySQL Support'); ?>:</td>
<td>
<?php
	if (!check_php_mysql()) {
		echo debug_result('',false);
		Error::add('install', T_('PHP MySQL Support'));
	}
	else {
		echo debug_result(Dba::get_client_info(), true);
	}
?>
</td>
</tr><tr>
<td><?php echo T_('PHP Session Support'); ?>:</td>
<td>
<?php
	if (!check_php_session()) {
		echo debug_result('',false);
		Error::add('install', T_('PHP Session Support'));
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<td><?php echo T_('PHP iconv Support'); ?>:</td>
<td>
<?php
	if (!check_php_iconv()) {
		echo debug_result('',false);
		Error::add('install', T_('PHP iconv Support'));
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<td><?php echo T_('PHP PCRE Support'); ?>:</td>
<td>
<?php
	if (!check_php_pcre()) {
		echo debug_result('',false);
		Error::add('install', T_('PHP PCRE Support'));
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<th colspan="2"><h4><?php echo T_('Optional'); ?></h4></th>
</tr><tr>
<td><?php echo T_('PHP gettext Support'); ?>:</td>
<td>
<?php
	if (!check_gettext()) {
		echo debug_result(T_('gettext emulation will be used'), false);
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<td><?php echo T_('PHP mbstring Support'); ?>:</td>
<td>
<?php
	if (!check_mbstring()) {
		echo debug_result(T_('Multibyte character encodings may not be autodetected correctly'), false);
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<td><?php echo T_('PHP Safe Mode'); ?>:</td>
<td>
<?php
	if (!check_safemode()) {
		echo debug_result(T_('Safe mode enabled'), false);
	}
	else {
		echo debug_result(T_('Safe mode not enabled'), true);
	}
?>
</td>
</tr><tr>
<td><?php echo T_('PHP Memory Limit'); ?>:</td>
<td>
<?php
	if (!check_php_memory()) {
		echo debug_result(T_('Memory limit less than recommended size') . ' ' . ini_get('memory_limit'), false);
	}
	else {
		echo debug_result(ini_get('memory_limit'),true);
	}

?>
</td>
</tr><tr>
<td><?php echo T_('PHP Execution Time Limit'); ?>:</td>
<td>
<?php
	if (!check_php_timelimit()) {
		echo debug_result(sprintf(T_('Execution time limit is %s seconds, which is less than recommended'), ini_get('max_execution_time')), false);
	}
	else {
		echo debug_result(ini_get('max_execution_time') . ' ' .  T_('seconds'),true);
	}
?>
</td>
</tr><tr>
<td><?php echo T_('ampache.cfg.php is writable'); ?></td>
<td>
<?php
	if (!check_config_writable()) {
		echo debug_result('', false);
	}
	else {
		echo debug_result('', true);
	}
?>
</td>
</tr>
</table>
