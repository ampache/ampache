<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

class AmpacheLastfm { 

	var $name		='Last.FM'; 
	var $description	='Records your played songs to your Last.FM Account'; 
	var $url		='';
	var $version		='000001';
	var $min_ampache	='333001';
	var $max_ampache	='333005';

	/**
	 * Constructor
	 * This function does nothing...
	 */
	function PluginLastfm() { 

		return true; 

	} // PluginLastfm

	/**
	 * install
	 * This is a required plugin function it inserts the required preferences
	 * into Ampache
	 */
	function install() { 

		/* We need to insert the new preferences */
		$sql = "INSERT INTO preferences (`name`,`value`,`description`,`level`,`type`,`catagory`) " . 
			"VALUES ('lastfm_user',' ','Last.FM Username','25','string','options')";
		$db_results = mysql_query($sql,dbh());

		$sql = "INSERT INTO preferences (`name`,`value`,`description`,`level`,`type`,`catagory`) " . 
			"VALUES ('lastfm_pass',' ','Last.FM Password','25','string','options')";
		$db_results = mysql_query($sql,dbh());

		fix_all_users_prefs(); 

	} // install

	/**
	 * uninstall
	 * This is a required plugin function it removes the required preferences from
	 * the database returning it to its origional form
	 */
	function uninstall() { 

		/* We need to remove the preivously added preferences */
		$sql = "DELETE FROM preferences WHERE name='lastfm_pass' OR name='lastfm_user'"; 
		$db_results = mysql_query($sql,dbh());

		fix_all_users_prefs();

	} // uninstall

} // end AmpacheLastfm
?>
