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
	@header Access Class
*/

class Access {

	/* Variables from DB */
	var $id;
	var $name;
	var $start;
	var $end;
	var $level;

	/*!
		@function Access
		@discussion Access class, for modifing access rights
		@param $access_id 	The ID of access entry
	 */
	function Access($access_id = 0) {

		/* If we have passed an id then do something */
		if ($access_id) { 

			/* Assign id for use in get_info() */
			$this->id = $access_id;

			/* Get the information from the db */
			if ($info = $this->get_info()) {

				/* Assign Vars */
				$this->name 	= $info->name;
				$this->start 	= $info->start;
				$this->end	= $info->end;
				$this->level	= $info->level;
			} // if info

		} // if access_id

	} //constructor

	/*!
		@function get_info
		@discussion get's the vars for $this out of the database 
		@param $this->id	Taken from the object
	*/
	function get_info() {

		/* Grab the basic information from the catalog and return it */
		$sql = "SELECT * FROM access_list WHERE id='$this->id'";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_object($db_results);

		return $results;

	} //get_info

	/*!
		@function create
		@discussion creates a new entry
	*/
	function create($name,$start,$end,$level) { 

		$start 	= ip2int($start);
		$end 	= ip2int($end);
		$name	= sql_escape($name);
		$level	= intval($level);

		$sql = "INSERT INTO access_list (`name`,`level`,`start`,`end`) VALUES ".
			"('$name','$level','$start','$end')";
		$db_results = mysql_query($sql, dbh());

	} // create

	/*!
		@function delete
		@discussion deletes $this access_list entry
	*/
	function delete($access_id=0) { 

		if (!$access_id) { 
			$access_id = $this->id;
		}

		$sql = "DELETE FROM access_list WHERE id='$access_id'";
		$db_results = mysql_query($sql, dbh());

	} // delete

	/*!
		@function check
		@discussion check to see if they have rights
	*/
	function check($needed, $ip) { 

		// They aren't using access control 
		// lets just keep on trucking
		if (!conf('access_control')) { 
			return true;
		} 

		$ip = ip2int($ip);

		$sql = "SELECT id FROM access_list WHERE start<='$ip' AND end>='$ip' AND level>='$needed'";
		$db_results = mysql_query($sql, dbh());

		// Yah they have access they can use the mojo
		if (mysql_fetch_row($db_results)) { 
			return true;
		}

		// No Access Sucks to be them.
		else { 
			return false;
		}

	} // check

	/*!
		@function get_access_list
		@discussion returns a full listing of all access
			rules on this server
	*/
	function get_access_list() { 

		$sql = "SELECT * FROM access_list";
		$db_results = mysql_query($sql, dbh());
		
		
		while ($r = mysql_fetch_object($db_results)) {
			$obj = new Access();
			$obj->id 	= $r->id;
			$obj->start 	= $r->start;
			$obj->end	= $r->end;
			$obj->name	= $r->name;
			$obj->level	= $r->level;
			$results[] = $obj;
		} // end while access list mojo

		return $results;

	} // get_access_list


	/*! 
		@function get_level_name
		@discussion take the int level and return a 
			named level
	*/
	function get_level_name() { 

		if ($this->level == '75') { 
			return "Full Access";
		}
		if ($this->level == '5') { 
			return "Demo";
		}
		if ($this->level == '25') { 
			return "Stream";
		}
		if ($this->level == '50') { 
			return "Stream/Download";
		}


	} // get_level_name

} //end of access class

?>
