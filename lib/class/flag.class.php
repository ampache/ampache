<?php
/*

 Copyright 2001 - 2006 Ampache.org
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
 * Flag Class
 * This handles flagging of songs, albums and artists	
 */
class Flag {

	/* DB based variables */
	var $id; 
	var $user;
	var $object_id;
	var $object_type;
	var $comment;
	var $flag;

	/**
	 * Constructor
	 * This takes a flagged.id and then pulls in the information for said flag entry
	 */
	function Flag($flag_id=0) { 

		$this->id = intval($flag_id);

		if (!$this->id) { return false; }

		$info = $this->_get_info();

		$this->user		= $info['user'];
		$this->object_id	= $info['object_id'];
		$this->object_type	= $info['object_type'];
		$this->comment		= $info['comment'];
		$this->flag		= $info['flag'];

		return true;

	} // flag

	/**
	 * _get_info
	 * Private function for getting the information for this object from the database 
	 */
	function _get_info() { 

		$id = sql_escape($this->id);

		$sql = "SELECT * FROM flagged WHERE id='$id'";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_assoc($db_results);

		return $results;

	} // _get_info

	/**
	 * add
	 * This adds a flag entry for an item, it takes an id, a type, the flag type
	 * and a comment and then inserts the mofo
	 */
	function add($id,$type,$flag,$comment) { 
	
		$id 		= sql_escape($id);
		$type		= sql_escape($type);
		$flag		= sql_escape($flag);
		$comment	= sql_escape($comment);

		$sql = "INSERT INTO flagged (`object_id`,`object_type`,`flag`,`comment`) VALUES " . 
			" ('$id','$type','$flag','$comment')";
		$db_results = mysql_query($sql, dbh());

		return true;

	} // add

} //end of flag class

?>
