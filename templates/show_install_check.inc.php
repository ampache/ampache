<?php
/*

 Copyright (c) Ampache.org
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
?>
<?php if (INSTALL != '1') { exit; } ?>
<h4><?php echo _('Required'); ?></h4>
<p><?php echo _('PHP Version'); ?>:
<?php
	if(!check_php_ver()) {
		$string = phpversion() . " " . _('Hash Function Exists') . " " . print_boolean(function_exists('hash_algos')) . " " . _('SHA256 Support') . " " . print_boolean(in_array('sha256',$algos));
		echo debug_result($string,false); 
		Error::add('install',_('PHP Version')); 
	} 
	else { 
		echo debug_result(phpversion(),true); 
	}
?>
</p>
<p><?php echo _('Mysql for PHP'); ?>:
<?php
	if (!check_php_mysql()) {
		echo debug_result('',false); 
		Error::add('install',_('Mysql for PHP')); 
	} 
	else {
		echo debug_result(mysql_get_client_info(),true); 
	}
?>
</p>
<p><?php echo _('PHP Session Support'); ?>:
<?php
	if (!check_php_session()) {
		echo debug_result('',false); 
		Error::add('install',_('PHP Session Support')); 
	} 
	else {
		echo debug_result('',true); 
	}
?>
</p>
<p><?php echo _('PHP ICONV Support'); ?>:
<?php
	if (!check_php_iconv()) {
		echo debug_result('',false); 
		Error::add('install',_('PHP ICONV Support')); 
	} 
	else {
		echo debug_result('',true); 	
	}
?>
</p>
<p><?php echo _('PHP PCRE Support'); ?>:
<?php
	if (!check_php_pcre()) {
		echo debug_result('',false); 
		Error::add('install',_('PHP PCRE Support')); 
	} 
	else {
		echo debug_result('',true); 
	}
?>
</p>
<p><?php echo _('PHP PutENV Support'); ?>:
<?php
	if (!check_putenv()) {
		echo debug_result('',false); 
		Error::add('install',_('PHP PutENV Support')); 
	} 
	else {
		echo debug_result('',true); 
	}
?>
</p>
<hr />
<h4><?php echo _('Optional'); ?></h4>
<p><?php echo _('Gettext Support'); ?>:
<?php
	if (!check_gettext()) { 	
		echo debug_result(_('Gettext Emulator will be used'),false); 
	} 
	else {
		echo debug_result('',true); 
	}
?>
</p>
<p><?php echo _('Mbstring Support'); ?>:
<?php
	if (!check_mbstring()) { 
		echo debug_result(_('Multibyte Chracter may not detect correct'),false); 
	} 
	else {
		echo debug_result('',true); 
	}
?>
</p>
