<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/**
 * verify_localplay_prefrences
 * This takes a type of localplay and then
 * Verifys that the preferences have all been 
 * inserted into the database if they haven't been
 * Then it returns false 
 */
function verify_localplay_preferences($type) { 

	/* Load the locaplay module of said type */
	$localplay = new Localplay($type); 

	$preferences = $localplay->get_preferences();

	foreach ($preferences as $preference) { 
		$name = 'localplay_' . $type . '_' . $preference['name'];
		/* check for an existing record */
		$sql = "SELECT id FROM preferences WHERE name = '" . sql_escape($name) . "'";
		$db_results = mysql_query($sql, dbh());

		if (!mysql_num_rows($db_results)) { return false; } 

	} // end foreach preferences

	return true;

} // verify_localplay_preferences


/**
 * insert_locaplay_preferences
 * This takes a controller type and inserts the preferences
 * Into the database, it is able to handle existing preferences
 * It checks before inserting...
 */
function insert_localplay_preferences($type) { 

	/* We can't assume the connect so let's just
	 * create it then get the preferences 
	 */
	$localplay = new Localplay($type);

	$preferences = $localplay->get_preferences(); 
	
	foreach ($preferences as $preference) { 
		$name = 'localplay_' . $type . '_' . $preference['name'];
		/* Check for an existing record */
		$sql = "SELECT id FROM preferences WHERE name = '" . sql_escape($name) . "'";
		$db_results = mysql_query($sql, dbh());

		if (mysql_num_rows($db_results)) { continue; } 

		insert_preference($name,$preference['description'],$preference['default'],'25',$preference['type'],'streaming');

	} // end foreach preferences

	/* Fix everyones preferences */
	$sql = "SELECT * FROM user";
	$db_results = mysql_query($sql, dbh());

	$temp_user = new User();
	$temp_user->fix_preferences('-1');

	while ($r = mysql_fetch_assoc($db_results)) { 
		$temp_user->fix_preferences($r['username']);
	} // end while

	return true;

} // insert_localplay_preferences

/**
 * remove_localplay_preferences
 * This function has two uses, if it is called with a specific type then it 
 * just removes the preferences for that type, however it if its called with
 * nothing then it removes any set of preferences where the module no longer
 * exists
 */
function remove_localplay_preferences($type=0) { 

	/* If we've gotten a type wipe and go! */
	if ($type) { 
		$sql = "DELETE FROM preferences WHERE name LIKE 'localplay_" . sql_escape($type) . "_%'";
		$db_results = mysql_query($sql, dbh());
		return true;
	}


	/* Select everything but our two built-in ones
	$sql = "SELECT * FROM preferences WHERE name != 'localplay_level' AND name != 'localplay_controller' AND name LIKE 'localplay_%'";
	$db_results = mysql_query($sql, dbh());

	$results = array();

	/* We need to organize by module to make it easy 
	 * to figure out which modules no longer exist 
	 * and wack the preferences... unless we've
	 * specified a name then just wack those 
	 * preferences
	 */
	while ($r = mysql_fetch_assoc($db_results)) { 

		$name = $r['name']; 
		preg_match("/localplay_([\w\d\-\]+)_.+/",$name,$matches);
		$key = $matches['1'];

		$results[$key] = $r;

	} // end while

	/* If we've got a type */
	//FIXME!!!	


} // remove_localplay_preferences

/**
 * get_localplay_controllers
 * This returns an array of the localplay controllers filenames
 * as well as a 'semi-cleaned' name
 */
function get_localplay_controllers() { 

	/* First get a list of the files */
	$handle = opendir(conf('prefix') . '/modules/localplay');
	
	if (!is_resource($handle)) { 
		debug_event('localplay','Error: Unable to read localplay controller directory','1');
	}

	$results = array(); 

	while ($file = readdir($handle)) { 
		
		if (substr($file,-14,14) != 'controller.php') { continue; } 

		/* Make sure it isn't a subdir */
		if (!is_dir($file)) { 
			/* Get the base name, then get everything before .controller.php */
			$filename = basename($file,'.controller.php');
			$results[] = $filename;
		}
	} // end while

	return $results;

} // get_localplay_controllers


/** 
 * This function stores the Localplay object
 * It checks to see what access level you have
 * and creates the localplay object based on that
 * @package Local Play
 */
function init_localplay($reload=0) {

	static $localplay;
	if ($GLOBALS['user']->prefs['localplay_level'] == '0') { return false; }

	if ($GLOBALS['user']->prefs['localplay_level'] == '1' AND !is_object($localplay)) { 
		$localplay = new Localplay(conf('localplay_controller'));
		$localplay->connect();
	}

	if ($GLOBALS['user']->prefs['localplay_level'] == '2' AND !is_object($localplay)) { 
		$localplay = new Localplay($GLOBALS['user']->prefs['localplay_controller']);
		$localplay->connect();
	}

        return $localplay;

} // function init_localplay

?>
