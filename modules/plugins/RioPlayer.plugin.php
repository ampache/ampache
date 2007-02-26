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

class AmpacheRioPlayer { 

	var $name		='Rio Player'; 
	var $description	='Sets up ampache so  a Rio Player can access it'; 
	var $url		='';
	var $version		='000001';
	var $min_ampache	='333001';
	var $max_ampache	='333005';

	/**
	 * Constructor
	 * This function does nothing...
	 */
	function PluginRioPlayer() { 

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
                        "VALUES ('rio_querylimit','3000','Rio Player Query Limit','100','integer','system')";
                $db_results = mysql_query($sql,dbh());

                $sql = "INSERT INTO preferences (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
                        "VALUES ('rio_track_stats','0','Rio Player Track Stats','100','boolean','system')";
                $db_results = mysql_query($sql,dbh());

                $sql = "INSERT INTO preferences (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
                        "VALUES ('rio_user','','Rio Player Global User','100','string','system')";
                $db_results = mysql_query($sql,dbh());

                $sql = "INSERT INTO preferences (`name`,`value`,`description`,`level`,`type`,`catagory`) " .
                        "VALUES ('rio_global_stats','0','Rio Player Group Stats','100','boolean','system')";
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

                $sql = "DELETE FROM preferences WHERE name='rio_querylimit' OR name='rio_track_stats' OR name='rio_user' OR name='rio_global_stats'";
                $db_results = mysql_query($sql,dbh());

                fix_all_users_prefs();

	} // uninstall

} // end AmpacheRioPlayer
?>
