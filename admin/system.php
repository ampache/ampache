<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

require '../lib/init.php';
require_once Config::get('prefix') . '/lib/debug.lib.php';
require_once Config::get('prefix') . '/modules/horde/Browser.php';

if (!$GLOBALS['user']->has_access(100)) {
	access_denied();
	exit();
}


/* Switch on action boys */
switch ($_REQUEST['action']) { 
	/* This re-generates the config file comparing
	 * /config/ampache.cfg to .cfg.dist
	 */
	case 'generate_config':
		$current = parse_ini_file(Config::get('prefix') . '/config/ampache.cfg.php');
		$final = generate_config($current);
	        $browser = new Browser(); 
	        $browser->downloadHeaders('ampache.cfg.php','text/plain',false,filesize('config/ampache.cfg.php.dist')); 
	        echo $final; 

	break;
	/* Check this version against ampache.org's record */
	case 'check_version':


	break;
	/* Export Catalog to ItunesDB */
	case 'export':
	    $catalog = new Catalog();
	    switch ($_REQUEST['export']) {
	    case 'itunes':
        	        header("Cache-control: public");
                	header("Content-Disposition: filename=itunes.xml");
	                header("Content-Type: application/itunes+xml; charset=utf-8");
        	        echo xml_get_header('itunes');
                	echo $catalog->export($_REQUEST['export']);
	                echo xml_get_footer('itunes');
	    break;
	    default:
        	$url    = conf('web_path') . '/admin/index.php';
	        $title  = _('Export Failed');
	        $body   = '';
	        show_template('header');
	        show_confirmation($title,$body,$url);
	        show_template('footer');
	    break;
	    }
	
	break;

	default: 
		// Rien a faire
	break;
} // end switch

?>
