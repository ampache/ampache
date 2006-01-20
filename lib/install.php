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
	@header Install docuement
	@discussion this document contains the functions needed to see if 
		ampache needs to be installed
*/

/*!
	@function split_sql
	@discussion splits up a standard SQL dump file into distinct
		sql queryies 
*/
function split_sql($sql) {
        $sql = trim($sql);
        $sql = ereg_replace("\n#[^\n]*\n", "\n", $sql);
        $buffer = array();
        $ret = array();
        $in_string = false;
        for($i=0; $i<strlen($sql)-1; $i++) {
                if($sql[$i] == ";" && !$in_string) {
                        $ret[] = substr($sql, 0, $i);
                        $sql = substr($sql, $i + 1);
                        $i = 0;
                }
                if($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\") {
                        $in_string = false;
                }
                elseif(!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset($buffer[0]) || $buffer[0] != "\\")) {
                        $in_string = $sql[$i];
                }
                if(isset($buffer[1])) {
                        $buffer[0] = $buffer[1];
                }
                $buffer[1] = $sql[$i];
        }
        if(!empty($sql)) {
                $ret[] = $sql;
        }
        return($ret);
} // split_sql

/*!
	@function install_check_status()
	@discussion this function checks to see if we actually
		still need to install ampache. This function is
		very important, we don't want to reinstall over top
		of an existing install
*/
function install_check_status($configfile) { 

	/* 
	  Check and see if the config file exists
	  if it does they can't use the web interface
	  to install ampache. 
	*/
	if (!file_exists($configfile)) { 
		return true;	
	}

	/* 
	  Check and see if they've got _any_ account
	  if they don't then they're cool
	*/
	$results = read_config($GLOBALS['configfile'], 0, 0);
	$dbh = check_database($results['local_host'],$results['local_username'],$results['local_pass']);	
		
	if (is_resource($dbh)) { 
		mysql_select_db($results['local_db'],$dbh);
		$sql = "SELECT * FROM user";
		$db_results = @mysql_query($sql, $dbh);
		if (!@mysql_num_rows($db_results)) { 
			return true;
		}
	}

	
	/* Defaut to no */
	return false;

} // install_check_status

/*!
	@function install_insert_db()
	@discussion this function inserts the database 
		using the username/pass/host provided
		and reading the .sql file
*/
function install_insert_db($username,$password,$hostname,$database) {

	/* Attempt to make DB connection */
	$dbh = @mysql_pconnect($hostname,$username,$password);
	
	/* Check/Create Database as needed */
	$db_selected = @mysql_select_db($database, $dbh);
	if (!$db_selected) { 
		$sql = "CREATE DATABASE `" . $database . "`";
		if (!$db_results = @mysql_query($sql, $dbh)) { 
			$GLOBALS['error']->add_error('general',"Error: Unable to Create Database " . mysql_error());
			return false;
		}
		@mysql_select_db($database, $dbh);
	} // if db can't be selected
	/* Check and see if we should create a user here */
	if ($_REQUEST['db_user'] == 'create_db_user') { 
		$db_user = scrub_in($_REQUEST['db_username']);
		$db_pass = scrub_in($_REQUEST['db_password']);
		$sql = "GRANT ALL PRIVILEGES ON " . sql_escape($database,$dbh) . ".* TO " .
			"'" . sql_escape($db_user,$dbh) . "'@'" . sql_escape($hostname,$dbh) . "' IDENTIFIED BY '" . sql_escape($db_pass,$dbh) . "' WITH GRANT OPTION";	

		if (!$db_results = @mysql_query($sql, $dbh)) { 
			$GLOBALS['error']->add_error('general',"Error: Unable to Insert $db_user with permissions to $database on $hostname " . mysql_error());
			return false;
		}
	} // end if we are creating a user

	/* Attempt to insert database */
         $query = fread(fopen("sql/ampache.sql", "r"), filesize("sql/ampache.sql"));
         $pieces  = split_sql($query);
         for ($i=0; $i<count($pieces); $i++) {
                 $pieces[$i] = trim($pieces[$i]);
                 if(!empty($pieces[$i]) && $pieces[$i] != "#") {
			   //FIXME: This is for a DB prefix when we get around to it
//                         $pieces[$i] = str_replace( "#__", $DBPrefix, $pieces[$i]);
                         if (!$result = mysql_query ($pieces[$i])) {
                                 $errors[] = array ( mysql_error(), $pieces[$i] );
                         } // end if
                 } // end if
         } // end for

	return true;

} // install_insert_db

/*!
	@function install_create_config()
	@discussion attempts to write out the config file
		if it can't write it out it will prompt the
		user to download the config file.
*/
function install_create_config($web_path,$username,$password,$hostname,$database) { 

	/* 
	  First Test The Variables they've given us to make
	  sure that they actually work!
	*/
	// Connect to the DB
	if(!$dbh = @mysql_pconnect($hostname,$username,$password)) { 
		$GLOBALS['error']->add_error('general',"Database Connection Failed Check Hostname, Username and Password");
		return false;
	}
	if (!$db_selected = @mysql_select_db($database, $dbh)) { 
		$GLOBALS['error']->add_error('general',"Database Selection Failure Check Existance of $database");
		return false;
	}


	/* Read in the .dist file and spit out the .cfg */
	$dist_handle 	= @fopen("config/ampache.cfg.php.dist",'r');
	$dist_data 	= @fread($dist_handle,filesize("config/ampache.cfg.php.dist"));
	fclose($dist_handle);

	$dist_array = explode("\n",$dist_data);

	// Rather then write it out right away, let's build the string
	// incase we can't write to the FS and have to make it a download

	foreach ($dist_array as $row) { 

		if (preg_match("/^#?web_path\s*=/",$row)) { 
			$row = "web_path = \"$web_path\"";
		}
		elseif (preg_match("/^#?local_db\s*=/",$row)) { 
			$row = "local_db = \"$database\"";
		}
		elseif (preg_match("/^#?local_host\s*=/",$row)) { 
			$row = "local_host = \"$hostname\"";
		}
		elseif (preg_match("/^#?local_username\s*=/",$row)) { 
			$row = "local_username = \"$username\"";
		}
		elseif (preg_match("/^#?local_pass\s*=/",$row)) { 
			$row = "local_pass = \"$password\"";
		}
	
		$config_data .= $row . "\n";

	} // foreach row in config file	

	/* Attempt to Write out File */
	if (!$config_handle = @fopen("config/ampache.cfg.php",'w')) { 
		$browser = new Browser();
		$browser->downloadHeaders("ampache.cfg.php","text/plain",false,filesize("config/ampache.cfg.php.dist"));
		echo $config_data;
		exit();
		
	}
	if (!@fwrite($config_handle,$config_data)) {
		$GLOBALS['error']->add_error('general',"Error: Unable to write Config File but file writeable?");
		return false;
	}

	return true;

} // install_create_config

/*!
	@function install_create_account
	@discussion this creates your initial account
*/
function install_create_account($username,$password) { 

        $results = read_config($GLOBALS['configfile'], 0, 0);
	$dbh = check_database($results['local_host'],$results['local_username'],$results['local_pass']);
		
	@mysql_select_db($results['local_db'],$dbh);

	$username = sql_escape($username,$dbh);
	$password = sql_escape($password,$dbh);

	$sql = "INSERT INTO user (`username`,`password`,`offset_limit`,`access`) VALUES ('$username',PASSWORD('$password'),'50','admin')";
	$db_results = mysql_query($sql, $dbh);
	
	if (!$db_results) { 
		$GLOBALS['error']->add_error('general',"Insert of Base User Failed " . mysql_error());
		return false; 
	}

	return true;
		
} // install_create_account

?>
