<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

if(php_sapi_name() != 'cli') {
    exit(1);
}

define('NO_SESSION', 1);
define('CLI', 1);

$path   = dirname(__FILE__);
$prefix = realpath($path . '/../../');
$a_root = realpath(__DIR__ . "/../../");
require_once $a_root . '/lib/init.php';

$options = getopt('e:l:n:p:u:w:');
if (empty($options) || !isset($options['u'])) {
    echo T_("Usage:") . "\n";
    echo "add_user.inc -u <" . T_('Username') .
        "> [ -l <" . T_('Access Level') .
        "> ] [ -p <" . T_('Password') .
        "> ] [ -e <" . T_('E-mail') .
        "> ] [ -w <" . T_('Website') .
        "> ] [ -n <" . T_('Name') . "> ]\n";
    exit(1);
}

$username = $options['u'];
$password = isset($options['p']) ? $options['p'] : mt_rand();
$access   = isset($options['l']) ? $options['l'] : AmpConfig::get('auto_user');
$access   = (isset($access)) ? $access : 5;
$access   = is_numeric($access) ? (int) $access : User::access_name_to_level($access);
$email    = isset($options['e']) ? $options['e'] : '';
$website  = isset($options['w']) ? $options['w'] : '';
$name     = isset($options['n']) ? $options['n'] : '';
$user_id  = User::create($username, $name, $email, $website, $password, $access);
if ($user_id < 1) {
    echo T_('User creation failed'), "\n";
    exit(1);
}
/* HINT: %1 Access Level (Administrator/User/etc), %2 Username, %3 password */
printf(T_('Created %1$s user %2$s with password %3$s'), User::access_level_to_name((string) $access), $username, $password);
echo "\n";

User::fix_preferences('-1');
