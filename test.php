<?php
/*

 Copyright 2001 - 2006 Ampache.org
 All Rights Reserved

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
// Set the Error level manualy... I'm to lazy to fix notices
error_reporting(E_ALL ^ E_NOTICE);


$prefix = dirname(__FILE__);
$configfile = "$prefix/config/ampache.cfg.php";


require_once($prefix . "/lib/general.lib.php");
require_once($prefix . "/lib/ui.lib.php");
require_once($prefix . "/lib/class/error.class.php");
$error = new error();
require_once($prefix . "/lib/debug.lib.php");



switch ($_REQUEST['action']) { 

	case 'verify_config':
		// This reads the ampache.cfg and compares the potential options against
		// those in ampache.cfg.dst
		show_compare_config($prefix); 
		break;
	default:
		require_once($prefix . "/templates/show_test.inc");
		break;
} // end switch on action



?> 
