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

/*!
	@function get_themes() 
	@discussion this looks in /themes and pulls all of the 
		theme.cfg.php files it can find and returns an 
		array of the results
*/
function get_themes() { 

	/* Open the themes dir and start reading it */
	$handle = @opendir(conf('prefix') . "/themes");

	if (!is_resource($handle)) { 
	 debug_event('theme',"Error unable to open Themes Directory",'2'); 
	}

	$results = array(); 

	while ($file = readdir($handle)) { 
		
		$full_file = conf('prefix') . "/themes/" . $file;
		/* See if it's a directory */
		if (is_dir($full_file) AND substr($file,0,1) != ".") { 
			$config_file = $full_file . "/theme.cfg.php";
			/* Open the theme.cfg.php file */
			$r = read_config($config_file);
			$r['path'] = $file;
			$name = $r['name']; 
			$results[$name] = $r;
		}

	} // end while directory

	// Sort by the theme name
	ksort($results); 

	return $results;

} // get_themes

/*!
	@function get_theme
	@discussion get a single theme and read the config file
		then return the results
*/
function get_theme($name) { 

	if (strlen($name) < 1) { return false; }

	$config_file = conf('prefix') . "/themes/" . $name . "/theme.cfg.php";
	$results = read_config($config_file);
	$results['path'] = $name;
	return $results;

} // get_theme

/*!
	@function set_theme
	@discussion Resets all of the colors for this theme 
*/
function set_theme_colors($theme_name,$user_id) { 

	if (make_bool($user_id)) { 
		$user_sql = "`user`='$user_id' AND";
	}


	/* We assume if we've made it this far we've got the right to do it 
	   This could be dangerous but eah!
	*/
	$theme = get_theme($theme_name);	
	$GLOBALS['theme'] = $theme;
	if (!count($theme)) { return false; }

	foreach ($theme as $key=>$color) { 

		$sql = "SELECT id FROM preferences WHERE name='" . sql_escape($key) . "'";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_array($db_results);
		// Quick hack this needs to be fixed
		if ($results) { 
			$sql = "UPDATE user_preference SET `value`='" . sql_escape($color) . "' WHERE $user_sql " . 
				" preference='" . $results[0] . "'";
			$db_results = mysql_query($sql, dbh());
		}

	} // theme colors

} // set_theme_colors

/*!
	@function set_theme
	@discussion this sets the needed vars for the theme
*/
function set_theme() { 

	if (strlen(conf('theme_name')) > 0) { 
		$theme_path = "/themes/" . conf('theme_name');
		conf(array('theme_path'=>$theme_path),1);
	}

} // set_theme

/*!
	@function get_theme_author
	@discussion returns the author of this theme
*/
function get_theme_author($theme_name) { 

	$theme_path = conf('prefix') . "/themes/" . conf('theme_name') . "/theme.cfg.php";
	$results = read_config($theme_path);

	return $results['author'];

} // get_theme_author

/*!
	@function theme_exists
	@discussion this function checks to make sure that a theme actually exists
*/
function theme_exists($theme_name) { 

	$theme_path = conf('prefix') . "/themes/" . $theme_name . "/theme.cfg.php";

	if (!file_exists($theme_path)) { 
		return false; 
	}

	return true;

} // theme_exists

?>
