<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Install Check
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @package	Ampache
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

?>
<?php if (!defined('INSTALL')) { exit; } ?>
<h4><?php echo _('Required'); ?></h4>
<table border="0" cellspacing="0" cellpadding="3">
<tr>
<td><?php echo sprintf(_("%s is readable"),"ampache.cfg.php.dist"); ?></td>
<td>
<?php
	if (!is_readable($prefix . '/config/ampache.cfg.php.dist')) {
		echo debug_result('',false);
		Error::add('install',sprintf(_("%s is readable"),"ampache.cfg.php.dist"));
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr>
<tr>
<td><?php echo sprintf(_('%s is readable'), 'ampache.sql'); ?></td>
<td>
<?php
	if (!is_readable($prefix . '/sql/ampache.sql')) {
		echo debug_result('', false);
		Error::add('install', sprintf(_('%s is readable'), 'ampache.sql'));
	}
	else {
		echo debug_result('', true);
	}
?>
</td>
</tr>
<tr>
<td><?php echo _('PHP Version'); ?>:</td>
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
		$string = $version_string . _('Hash Function Exists') . " " . print_bool(function_exists('hash_algos')) . " " . _('SHA256 Support') . " " . print_bool(in_array('sha256',$algos));
		echo debug_result($string,false);
		Error::add('install',_('PHP Version'));
	}
	else {
		echo debug_result(phpversion(),true);
	}
?>
</td>
</tr><tr>
<td><?php echo _('PHP MySQL Support'); ?>:</td>
<td>
<?php
	if (!check_php_mysql()) {
		echo debug_result('',false);
		Error::add('install', _('PHP MySQL Support'));
	}
	else {
		echo debug_result(mysql_get_client_info(),true);
	}
?>
</td>
</tr><tr>
<td><?php echo _('PHP Session Support'); ?>:</td>
<td>
<?php
	if (!check_php_session()) {
		echo debug_result('',false);
		Error::add('install',_('PHP Session Support'));
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<td><?php echo _('PHP iconv Support'); ?>:</td>
<td>
<?php
	if (!check_php_iconv()) {
		echo debug_result('',false);
		Error::add('install', _('PHP iconv Support'));
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<td><?php echo _('PHP PCRE Support'); ?>:</td>
<td>
<?php
	if (!check_php_pcre()) {
		echo debug_result('',false);
		Error::add('install',_('PHP PCRE Support'));
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<th colspan="2"><h4><?php echo _('Optional'); ?></h4></th>
</tr><tr>
<td><?php echo _('PHP gettext Support'); ?>:</td>
<td>
<?php
	if (!check_gettext()) {
		echo debug_result(_('gettext emulation will be used'), false);
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<td><?php echo _('PHP mbstring Support'); ?>:</td>
<td>
<?php
	if (!check_mbstring()) {
		echo debug_result(_('Multibyte character encodings may not be autodetected correctly'), false);
	}
	else {
		echo debug_result('',true);
	}
?>
</td>
</tr><tr>
<td><?php echo _('PHP Safe Mode'); ?>:</td>
<?php
	if (!check_safemode()) {
		echo debug_result(_('Safe mode enabled'), false);
	}
	else {
		echo debug_result(_('Safe mode not enabled'), true);
	}
?>
</td>
</tr><tr>
<td><?php echo _('PHP Memory Limit'); ?>:</td>
<td>
<?php
	if (!check_php_memory()) {
		echo debug_result(_('Memory limit less than recommended size') . ' ' . ini_get('memory_limit'), false);
	}
	else {
		echo debug_result(ini_get('memory_limit'),true);
	}

?>
</td>
</tr><tr>
<td><?php echo _('PHP Execution Time Limit'); ?>:</td>
<td>
<?php
	if (!check_php_timelimit()) {
		echo debug_result(_('Execution time limit less than recommended') . ' ' . ini_get('max_execution_time'), false);
	}
	else {
		echo debug_result(ini_get('max_execution_time') . ' ' .  _('seconds'),true);
	}
?>
</td>
</tr><tr>
<td><?php echo _('ampache.cfg.php is writable'); ?></td>
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
