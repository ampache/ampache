<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

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
	@header View Object of crappyness
	View object that is thrown into their session

*/


class View {

	//Basic Componets
	var $base_sql;
	var $offset;
	var $offset_limit;
	var $sort_order; //asc or desc
	var $sort_type;
	var $action;
	var $total_items;

	//generate a new view
	function View($base_sql=0,$script=0,$sort_type=0,$total_items=0,$offset_limit=0) {
		global $conf;

		// If we don't have a base sql, stop here
		if (!is_string($base_sql)) {
			return true;
		}

		//Convert all 's into "s 
		$base_sql = str_replace("'",'"',$base_sql);	

		$this->base_sql = $base_sql;
		if ($offset_limit) { $this->offset_limit = $offset_limit; }
		else { $this->offset_limit = $_SESSION['offset_limit']; }
		if ($this->offset_limit < '1') { $this->offset_limit = '50'; }
		$this->script = $script;
		$this->sort_type = $sort_type;
		$this->sort_order = "ASC";
		$this->offset = 0;		
		$this->total_items = $total_items;

		// Set the session
		$_SESSION['view_offset_limit']  = $this->offset_limit;		
		$_SESSION['view_sort_type']	= $this->sort_type;
		$_SESSION['view_offset'] 	= $this->offset;
		$_SESSION['view_base_sql']	= $this->base_sql;
		$_SESSION['view_sort_order']	= $this->sort_order;
		$_SESSION['view_script']	= $this->script;
		$_SESSION['view_total_items']	= $this->total_items;
		$this->sql = $this->generate_sql();

	} //constructor

	//takes all the parts and makes a full blown sql statement
	function generate_sql() {
		global $conf;
			
		$sql = $this->base_sql . " ORDER BY " . $this->sort_type ." ". $this->sort_order ." LIMIT " . $this->offset . "," . $this->offset_limit;
	
		return $sql;
	
	} //generate_sql

	//change the sort order from asc to desc or vise versa
	function change_sort($new_sort=0) {
		global $conf;

		if ($new_sort) {
			$this->sort_order = $new_sort;
		}
		elseif ($this->sort_order == "DESC") {
			$this->sort_order = "ASC";
		}
		else {
			$this->sort_order = "DESC";
		}
		
		$_SESSION['view_sort_order'] = $this->sort_order;

		$this->sql = $this->generate_sql();

	return;

	} //change_sort

	//change the base sql
	function change_sql($base_sql) {
		global $conf;

		//Convert all 's into "s 
		$base_sql = str_replace("'",'"',$base_sql);	

		$this->base_sql = $base_sql;

		$_SESSION['view_base_sql'] = $this->base_sql;
	
		$this->sql = $this->generate_sql();

	} //change_sql

	//change offset
	function change_offset($offset=0) {
		global $conf;

		if (isset($offset)) {
			$this->offset = $offset;
		}
		else {
			$this->offset = $this->offset + $this->offset_limit;
		}

		$_SESSION['view_offset'] = $this->offset;

		$this->sql = $this->generate_sql();

	} //change_offset

	//change sort_type
	function change_sort_type($sort_type) {

		$this->sort_type = $sort_type;

		$_SESSION['view_sort_type'] = $this->sort_type;

		$this->sql = $this->generate_sql();

	} //change_sort_type

	/*!
		@function change_offset_limit
		@discussion changes the offset limit, sets the session
			    var and generates the sql statement
	*/
	function change_offset_limit($offset_limit) {

		$this->offset_limit = $offset_limit;

		$_SESSION['view_offset_limit'] = $this->offset_limit;

		$this->sql = $this->generate_sql();

	} // change_offset_limit

	/*!
		@function initialize
		@discussion initializes the view object, checks $_REQUEST
			    for changes to the view object
	*/
	function initialize($sql='') {

		/* From time to time we need to change the SQL statement while 
		 * maintaining the paging 
		 */
		if ($sql) { 
			$this->change_sql($sql);
		}

		if ($_REQUEST['sort_type']) {
			$this->change_sort_type($_REQUEST['sort_type']);
		}

		if (isset($_REQUEST['offset'])) {
			$this->change_offset($_REQUEST['offset']);
		}

		if ($_REQUEST['base_sql']) {
			$this->change_sql($_REQUEST['base_sql']);
		}

		if (isset($_REQUEST['sort_order'])) {
			$this->change_sort($_REQUEST['sort_order']);
		}

		if ($_REQUEST['offset_limit']) {
			$this->change_offset_limit($_REQUEST['offset_limit']);
		}

	} // initialize


	/*!
		@function import_session_view
		@discussion this imports the view from the session for use..
			    this keeps us from having to globalize anything
			    wohoo!
	*/
	function import_session_view() {

		$this->sort_type 	= $_SESSION['view_sort_type'];
		$this->offset		= $_SESSION['view_offset'];
		$this->base_sql		= $_SESSION['view_base_sql'];
		$this->sort_order	= $_SESSION['view_sort_order'];
		$this->script		= $_SESSION['view_script'];
		$this->total_items	= $_SESSION['view_total_items'];


		if ($_SESSION['view_offset_limit']) {
			$this->offset_limit	= $_SESSION['view_offset_limit'];
		} 
		else {
			$this->offset_limit	= $_SESSION['offset_limit'];
		}


		$this->sql = $this->generate_sql();

	} // import_session_view

		

} //end class
?>
