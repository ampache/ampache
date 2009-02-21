<?php
/*

 Copyright (c) Ampache.org
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

require '../lib/init.php';
require_once Config::get('prefix') . '/lib/debug.lib.php';
require_once Config::get('prefix') . '/modules/horde/Browser.php';

if (!Access::check('interface',100) OR Config::get('demo_mode')) {
	access_denied();
	exit();
}

show_header(); 

/* Switch on action boys */
switch ($_REQUEST['action']) { 
	/* This re-generates the config file comparing
	 * /config/ampache.cfg to .cfg.dist
	 */
	case 'generate_config':
		ob_end_clean(); 
		$current = parse_ini_file(Config::get('prefix') . '/config/ampache.cfg.php');
		$final = generate_config($current);
	        $browser = new Browser(); 
	        $browser->downloadHeaders('ampache.cfg.php','text/plain',false,filesize(Config::get('prefix') . '/config/ampache.cfg.php.dist')); 
	        echo $final; 
		exit; 
	break;
	case 'reset_db_charset':
		Dba::reset_db_charset(); 	
		show_confirmation(_('Database Charset Updated'),_('Your Database and associated tables have been updated to match your currently configured charset'),'/admin/system.php?action=show_debug'); 
	break; 
	case 'show_debug': 
                $configuration = Config::get_all();
                require_once Config::get('prefix') . '/templates/show_debug.inc.php';
	break; 
	default: 
		// Rien a faire
	break;
} // end switch


show_footer(); 

?>
