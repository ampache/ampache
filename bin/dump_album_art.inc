<?php
/*

 Copyright Ampache.org
 All Rights Reserved

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

define('NO_SESSION','1');
$path = dirname(__FILE__); 
$prefix = realpath($path . '/../'); 
require_once $prefix . '/lib/init.php';

// Turn off output buffering we don't need it for a command line script
ob_end_clean(); 

$meta = 'linux'; 

// Take input from the command line, two options linux or windows
if (count($_SERVER['argv']) > 1) { 
	$meta = ($_SERVER['argv']['1'] == 'windows') ? 'windows' : 'linux';  
}

$catalogs = Catalog::get_catalogs();

foreach ($catalogs as $catalog_id) { 
	Catalog::dump_album_art($catalog_id,array('metadata'=>$meta));
}


?>
