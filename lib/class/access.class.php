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
 * Access Class
 * This class handles the access list mojo for Ampache, it is ment to restrict
 * access based on IP and maybe something else in the future
*/

class Access {

	/* Variables from DB */
	var $id;
	var $name;
	var $start;
	var $end;
	var $level;
	var $user;
	var $type;
	var $key;

	/*!
		@function Access
		@discussion Access class, for modifing access rights
		@param $access_id 	The ID of access entry
	 */
	function Access($access_id = 0) {

		if (!$access_id) { return false; }


		/* Assign id for use in get_info() */
		$this->id = intval($access_id);

		$info = $this->get_info();
		$this->name 	= $info->name;
		$this->start 	= $info->start;
		$this->end	= $info->end;
		$this->level	= $info->level;
		$this->key	= $info->key;
		$this->user	= $info->user;
		$this->type	= $info->type;

		return true;

	} //Access

	/*!
		@function get_info
		@discussion get's the vars for $this out of the database 
		@param $this->id	Taken from the object
	*/
	function get_info() {

		/* Grab the basic information from the catalog and return it */
		$sql = "SELECT * FROM access_list WHERE id='" . sql_escape($this->id) . "'";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_object($db_results);

		return $results;

	} //get_info

	/**
	 * update
	 * This function takes a named array as a datasource and updates the current access list entry
	 */
	function update($data) { 

		$start 	= ip2int($data['start']);
		$end	= ip2int($data['end']);
		$level	= sql_escape($data['level']);
		$user	= sql_escape($data['user']);
		$key	= sql_escape($data['key']);
		
		if (!$user) { $user = '-1'; } 
		
		$sql = "UPDATE access_list " . 
			"SET start='$start', end='$end', level='$level', user='$user', `key`='$key' " . 
			"WHERE id='" . sql_escape($this->id) . "'";

		$db_results = mysql_query($sql, dbh());

		return true;

	} // update

	/*!
		@function create
		@discussion creates a new entry
	*/
	function create($name,$start,$end,$level,$user,$key,$type) { 

		/* We need to verify the incomming data a littlebit */

		$start 	= ip2int($start);
		$end 	= ip2int($end);
		$name	= sql_escape($name);
		$key	= sql_escape($key);
		$user	= sql_escape($user);
		$level	= intval($level);
		$type	= $this->validate_type($type);

		if (!$user) { $user = '-1'; } 

		$sql = "INSERT INTO access_list (`name`,`level`,`start`,`end`,`key`,`user`,`type`) " . 
			"VALUES ('$name','$level','$start','$end','$key','$user','$type')";
		$db_results = mysql_query($sql, dbh());

		return true;

	} // create

	/*!
		@function delete
		@discussion deletes $this access_list entry
	*/
	function delete($access_id=0) { 

		if (!$access_id) { 
			$access_id = $this->id;
		}

		$sql = "DELETE FROM access_list WHERE id='" . sql_escape($access_id) . "'";
		$db_results = mysql_query($sql, dbh());

	} // delete

	/*!
		@function check
		@discussion check to see if they have rights
	*/
	function check($type,$ip,$user,$level,$key='') { 

		// They aren't using access control 
		// lets just keep on trucking
		if (!conf('access_control')) { 
			return true;
		} 

		// Clean incomming variables
		$ip 	= ip2int($ip);
		$user 	= sql_escape($user);
		$key 	= sql_escape($key);
		$level	= sql_escape($level);

		switch ($type) { 
			/* This is here because we want to at least check IP before even creating the xml-rpc server
			 * however we don't have the key that was passed yet so we've got to do just ip
			 */
			case 'init-xml-rpc':
				$sql = "SELECT id FROM access_list" .
					" WHERE `start` <= '$ip' AND `end` >= '$ip' AND `type`='xml-rpc' AND `level` >= '$level'";
			break;
			case 'xml-rpc':
				$sql = "SELECT id FROM access_list" . 
					" WHERE `start` <= '$ip' AND `end` >= '$ip'" . 
					" AND  `key` = '$key' AND `level` >= '$level' AND `type`='xml-rpc'";
			break;
			case 'network':
			case 'interface':
			case 'stream':
			default:
				$sql = "SELECT id FROM access_list" . 
					" WHERE `start` <= '$ip' AND `end` >= '$ip'" .
					" AND `level` >= '$level' AND `type` = '$type'";
				if (strlen($user)) { $sql .= " AND (`user` = '$user' OR `user` = '-1')"; }
				else { $sql .= " AND `user` = '-1'"; }
			break;
		} // end switch on type
		
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

	/**
	 * validate_type
	 * This cleans up and validates the specified type
	 */
	function validate_type($type) { 

		switch($type) { 
			case 'xml-rpc':
			case 'interface':
			case 'network':
				return $type;
			break;
			default: 
				return 'stream';
			break;
		} // end switch
	} // validate_type

	/*!
		@function get_access_list
		@discussion returns a full listing of all access
			rules on this server
	*/
	function get_access_list() { 

		$sql = "SELECT * FROM access_list";
		$db_results = mysql_query($sql, dbh());
		
		// Man this is the wrong way to do it...
		while ($r = mysql_fetch_object($db_results)) {
			$obj = new Access();
			$obj->id 	= $r->id;
			$obj->start 	= $r->start;
			$obj->end	= $r->end;
			$obj->name	= $r->name;
			$obj->level	= $r->level;
			$obj->user	= $r->user;
			$obj->key	= $r->key;
			$obj->type	= $r->type;
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
			return "Read/Write/Modify";
		}
		if ($this->level == '5') { 
			return "View";
		}
		if ($this->level == '25') { 
			return "Read";
		}
		if ($this->level == '50') { 
			return "Read/Write";
		}


	} // get_level_name

	/**
 	 * get_user_name
	 * Take a user and return their full name
	 */
	function get_user_name() { 
		
		$user = new User($this->user);
		if ($user->username) { 
			return $user->fullname . " (" . $user->username . ")";
		}
		
		return false;

	} // get_user_name

	/**
	 * get_type_name
	 * This function returns the pretty name for our current type
	 */
	function get_type_name() { 

		switch ($this->type) { 
			case 'xml-rpc':
				return 'XML-RPC';
			break;
			case 'network':
				return 'Local Network Definition';
			break;
			case 'interface':
				return 'Web Interface';
			break;
			case 'stream':
			default: 
				return 'Stream Access';
			break;
		} // end switch
	} // get_type_name

} //end of access class

?>
