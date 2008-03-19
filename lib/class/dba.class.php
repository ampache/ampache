<?php
/*

 Copyright (c) 2001 - 2008 Ampache.org
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
/* Make sure they aren't directly accessing it */
if (INIT_LOADED != '1') { exit; }

/**
 * Dba
 * This is the database abstraction class
 * It duplicates the functionality of mysql_???
 * with a few exceptions, the row and assoc will always
 * return an array, simplifying checking on the far end
 * it will also auto-connect as needed, and has a default
 * database simplifying queries in most cases. 
 */
class Dba { 

	private static $_default_db;

	private static $_sql; 
	private static $config; 

	/**
	 * constructor
	 * This does nothing with the DBA class
	 */
	private function __construct() { 

		// Rien a faire

	} // construct

	/**
	 * query
	 * This is the meat of the class this does a query, it emulates
	 * The mysql_query function
	 */
	public static function query($sql) { 

		// Run the query
		$resource = mysql_query($sql,self::dbh()); 
		debug_event('Query',$sql,'6');
		
		// Save the query, to make debug easier
		self::$_sql = $sql; 

		return $resource; 

	} // query

	/**
	 * escape
	 * This runs a escape on a variable so that it can be safely inserted
	 * into the sql 
	 */
	public static function escape($var) { 

		$string = mysql_real_escape_string($var,self::dbh()); 
		
		return $string; 

	} // escape

	/**
	 * fetch_assoc
	 * This emulates the mysql_fetch_assoc and takes a resource result
	 * we force it to always return an array, albit an empty one
	 */
	public static function fetch_assoc($resource) { 

		$result = mysql_fetch_assoc($resource); 

		if (!$result) { 
			return array(); 
		} 

		return $result;

	} // fetch_assoc

	/**
	 * fetch_row
	 * This emulates the mysql_fetch_row and takes a resource result
	 * we force it to always return an array, albit an empty one
	 */
	public static function fetch_row($resource) { 

		$result = mysql_fetch_row($resource); 

		if (!$result) { 
			return array(); 
		} 

		return $result; 

	} // fetch_row

	/**
	 * num_rows
	 * This emulates the mysql_num_rows function which is really
	 * just a count of rows returned by our select statement, this
	 * doesn't work for updates or inserts
	 */
	public static function num_rows($resource) { 
		
		$result = mysql_num_rows($resource); 
		
		if (!$result) { 
			return '0'; 
		} 

		return $result;
	} // num_rows 

	/**
	 * affected_rows
	 * This emulates the mysql_affected_rows function
	 */
	public static function affected_rows($resource) { 

		$result = mysql_affected_rows($resource); 

		if (!$result) { 
			return '0'; 
		} 

		return $result; 

	} // affected_rows

	/**
	 * _connect
	 * This connects to the database, used by the DBH function
	 */
	private static function _connect($db_name) { 

		if (self::$_default_db == $db_name) { 
			$username = Config::get('database_username'); 
			$hostname = Config::get('database_hostname'); 
			$password = Config::get('database_password'); 
			$database = Config::get('database_name'); 
		} 
		else { 
			// Do this later
		} 

		$dbh = mysql_connect($hostname,$username,$password); 
		if (!$dbh) { debug_event('Database','Error unable to connect to database' . mysql_error(),'1'); } 

		if (function_exists('mysql_set_charset')) { 
			$sql_charset = str_replace("-","",Config::get('site_charset'));
			$charset = mysql_set_charset($sql_charset,$dbh); 
		} 
		else { 
			$sql = "SET NAMES " . mysql_real_escape_string(str_replace("-","",Config::get('site_charset'))); 
			$charset = mysql_query($sql,$dbh); 
		}
		if (!$charset) { debug_event('Database','Error unable to set connection charset, function missing or set failed','1'); }  

		$select_db = mysql_select_db($database,$dbh); 
		if (!$select_db) { debug_event('Database','Error unable to select ' . $database . ' error ' . mysql_error(),'1'); } 
		
		return $dbh;

	} // _connect

	/**
	 * dbh
	 * This is called by the class to return the database handle
	 * for the specified database, if none is found it connects
	 */
	public static function dbh($database='') { 

		if (!$database) { $database = self::$_default_db; } 

		// Assign the Handle name that we are going to store
		$handle = 'dbh_' . $database;
		
		if (!is_resource(Config::get($handle))) { 
			$dbh = self::_connect($database);
			Config::set($handle,$dbh,1); 
			return $dbh;
		} 
		else { 
			return Config::get($handle); 
		} 


	} // dbh

	/**
	 * insert_id
	 * This emulates the mysql_insert_id function, it takes
	 * an optional database target
	 */
	public static function insert_id() { 

		$id = mysql_insert_id(self::dbh()); 
		return $id; 

	} // insert_id

	/**
	 * error
	 * this returns the error of the db
	 */
	public static function error() { 

		return mysql_error(); 

	} // error

	/**
	 * auto_init
	 * This is the auto init function it sets up the config class
	 * and also sets the default database 
	 */
	public static function _auto_init() { 

		self::$_default_db = Config::get('database_name'); 

		return true; 

	} // auto_init

} // dba class

?>
