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

require('../lib/init.php');
require_once(conf('prefix') . '/lib/debug.lib.php');
require_once(conf('prefix') . '/modules/horde/Browser.php');

if (!$GLOBALS['user']->has_access(100)) {
	access_denied();
	exit();
}


$action = scrub_in($_REQUEST['action']);

/* Switch on action boys */
switch ($action) { 
	/* This re-generates the config file comparing
	 * /config/ampache.cfg to .cfg.dist
	 */
	case 'generate_config':
		
		$configfile 	= conf('prefix') . '/config/ampache.cfg.php';
		$distfile 	= conf('prefix') . '/config/ampache.cfg.php.dist';

		/* Load the current config file */
		$current 	= read_config($configfile, 0, 0);	
		
		/* Start building the new config file */
		$handle = fopen($distfile,'r'); 
		$dist = fread($handle,filesize($distfile));
		fclose($handle);
		
		$data = explode("\n",$dist);
		
		/* Run throught the lines and set our settings */
		foreach ($data as $line) { 

			/* Attempt to pull out Key */
			if (preg_match("/^#?([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/",$line,$matches)
        	                || preg_match("/^#?([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $line, $matches)
                	        || preg_match("/^#?([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/",$line,$matches)) {

				$key 	= $matches[1];
				$value	= $matches[2];
				
				/* Check to see if Key on source side is an array */
				if (is_array($current[$key])) { 
					/* We need to add all values of this key to the new config file */
					$line = '';
					$array_value[$key] = true;
					foreach ($current[$key] as $sub_value) { 
						$line .= $key . " = \"" . $sub_value . "\"\n";
					}
					unset($current[$key]); 
				} // is array
				
				/* Put in the current value */
				elseif (isset($current[$key]) AND $key != 'config_version') { 
					$line = $key . " = \"" . $current[$key] . "\"";
					unset($current[$key]);
				} // if set 

				elseif (isset($array_value[$key])) { 
					$line = '';
				}
			

			} // if key


			$final .= $line . "\n";	

		} // end foreach dist file contents

		/* Set Correct Headers */
		$browser = new Browser();
		$browser->downloadHeaders("ampache.cfg.php","text/plain",false,filesize("config/ampache.cfg.php.dist"));
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
