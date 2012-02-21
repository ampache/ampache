<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Test
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

// Set the Error level manualy... I'm to lazy to fix notices
error_reporting(0);

$prefix = dirname(__FILE__);
$configfile = "$prefix/config/ampache.cfg.php";
$row_classes = array('even','odd');

define('INIT_LOADED','1');

require_once $prefix . '/lib/general.lib.php';
require_once $prefix . '/lib/log.lib.php';
require_once $prefix . '/lib/class/config.class.php';
require_once $prefix . '/lib/class/dba.class.php';
require_once $prefix . '/lib/ui.lib.php';
require_once $prefix . '/lib/class/error.class.php';
require_once $prefix . '/lib/class/config.class.php';
require_once $prefix . '/lib/debug.lib.php';

Dba::_auto_init();

switch ($_REQUEST['action']) {
	case 'config':
		// Check to see if the config file is working now, if so fall
		// through to the default, else show the appropriate template
		$configfile = "$prefix/config/ampache.cfg.php";
		
		if (!count(parse_ini_file($configfile))) {
			require_once $prefix . '/templates/show_test_config.inc.php';
			break;
		}
	default:
		require_once $prefix . '/templates/show_test.inc.php';
	break;
} // end switch on action

?>
