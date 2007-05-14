<?php
/*

 Copyright 2001 - 2006 Ampache.org
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

/*
 * Use with caution, this hasn't been heavily tested!!!
 * write_tags.php.inc - This file was written in order to give the ability
 * to write tags changed through the interface back out to the file. This can
 * be especially important when trying to keep a clean file structure.
 */

define('NO_SESSION','1');
require ("../lib/init.php");


if (!$GLOBALS['argv']['1'] || $GLOBALS['argv']['1'] == '-h') { usage(); } 
else { 
	$dirname	= $GLOBALS['argv']['1']; 
	$type		= $GLOBALS['argv']['2']; 
} 


// Make sure the output dir is valid and writeable
if (!is_writeable($dirname)) { 
	echo "Error: Directory $dirname not writeable\n"; 
}

// Switch on the type of playlist dump we want to do 
// here
switch ($type) { 
	case 'playlists': 


	break;
	case 'artist': 

	break;
	default: 



	break;
} // end type switch

/* FUNCTIONS */
function usage() { 

$string = "write_playlists.php.inc [-h] <DIRNAME> <TYPE>

	This will dump a collection of m3u playlists based on type
	Types:
		default		Dumps all Albums as individual playlists
		playlists	Dumps all of your Playlists as m3u's
		artist		Dumps all Artists as individual playlists\n\n";

	exit($string); 

} // useage

?>
