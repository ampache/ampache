<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved

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

define('NO_SESSION','1');
require ('../lib/init.php');
require ('../lib/debug.lib.php');

$results = debug_read_config(conf('prefix') . "/config/ampache.cfg.php");

$dist_results = debug_read_config(conf('prefix') . "/config/ampache.cfg.php.dist");

echo "\nCHECKING CONF VARS... \n\n";

foreach ($dist_results['conf'] as $key=>$value) { 
	
	if (!isset($results['conf'][$key])) { 
		echo "MISSING:";
		echo " $key = $value\n";
	}

} // foreach dist

echo "\nCHECKING LIBGLUE VARS...\n\n";

foreach ($dist_results['libglue'] as $key=>$value) { 

        if (!isset($results['libglue'][$key])) {
	        echo "MISSING:";
	        echo " $key = $value\n";
        }
						

} // foreach libglue

?>

