<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2012 Ampache.org All Rights Reserved
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
 * @copyright	2001 - 2012 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

if(php_sapi_name() != 'cli') {
	exit(1);
}

define('NO_SESSION', 1);
define('CLI', 1);

$path = dirname(__FILE__);
$prefix = realpath($path . '/../../');
require_once $prefix . '/lib/init.php';

$options = getopt('e:l:n:p:u:');
if (!$options || !isset($options['u'])) { 
	echo "Usage: add_user.inc -u <username> [ -l <access level> ] [ -p <password ] [ -e <email> ] [ -n <name> ]\n";
	exit(1);
}

$username = $options['u'];
$password = isset($options['p']) ? $options['p'] : mt_rand();
$access = isset($options['l']) ? $options['l'] : Config::get('auto_user');
$access = isset($access) ? $access : 'guest';
$access = is_numeric($access) ? $access : User::access_name_to_level($access);
$email = isset($options['e']) ? $options['e'] : '';
$name = isset($options['n']) ? $options['n'] : '';

if (User::create($username, $name, $email, $password, $access)) {
	printf(_('Created %s user %s with password %s'), _($access), $username, $password);
	echo "\n";
}
else {
	echo _('User creation failed'), "\n";
	exit(1);
}

User::fix_preferences('-1');

?>
