<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

/**
 * split_sql
 * splits up a standard SQL dump file into distinct
 * sql queryies 
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

/**
 * install_check_status
 * this function checks to see if we actually
 * still need to install ampache. This function is
 * very important, we don't want to reinstall over top of an existing install
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
	$results = parse_ini_file($configfile);
	$dbh = check_database($results['database_hostname'],$results['database_username'],$results['database_password']);	

	if (is_resource($dbh)) { 
		@mysql_select_db($results['database_name'],$dbh);
		$sql = "SELECT * FROM `user`";
		$db_results = @mysql_query($sql, $dbh);
		if (!@mysql_num_rows($db_results)) { 
			return true;
		}
	}

	/* Defaut to no */
	return false;

} // install_check_status

/**
 * install_insert_db
 * this function inserts the database using the username/pass/host provided
 * and reading the .sql file
 */
function install_insert_db($username,$password,$hostname,$database) {

	// Make sure that the database name is valid
	$is_valid = preg_match("/([^\d\w\_])/",$database,$matches); 

	if (count($matches)) { 
		Error::add('general','Error: Database name invalid must not be a reserved word, and must be Alphanumeric'); 
		return false; 
	} 

	$data['database_username'] = $username; 
	$data['database_password'] = $password; 
	$data['database_hostname'] = $hostname; 
	$data['database_name']	   = $database;
	
	Config::set_by_array($data,'1'); 
	
	unset($data); 

	/* Attempt to make DB connection */
	$dbh = Dba::dbh();
	
	if (!is_resource($dbh)) { 
		Error::add('general','Error: Unable to make Database Connection ' . mysql_error()); 
		return false; 
	}

	/* Check/Create Database as needed */
	$db_selected = @mysql_select_db($database, $dbh);

	if ($db_selected && !$_POST['overwrite_db']) { 
		Error::add('general','Error: Database Already exists and Overwrite not checked'); 
		return false; 
	} 
	if (!$db_selected) { 
		$sql = "CREATE DATABASE `" . $database . "`";
		if (!$db_results = @mysql_query($sql, $dbh)) { 
			Error::add('general',"Error: Unable to Create Database " . mysql_error());
			return false;
		}
		@mysql_select_db($database, $dbh);
	} // if db can't be selected

	/* Check and see if we should create a user here */
	if ($_REQUEST['db_user'] == 'create_db_user') { 
		$db_user = scrub_in($_REQUEST['db_username']);
		$db_pass = scrub_in($_REQUEST['db_password']);
		$sql = "GRANT ALL PRIVILEGES ON " . Dba::escape($database) . ".* TO " .
			"'" . Dba::escape($db_user) . "'@'" . Dba::escape($hostname) . "' IDENTIFIED BY '" . Dba::escape($db_pass) . "' WITH GRANT OPTION";	

		if (!$db_results = @mysql_query($sql, $dbh)) { 
			Error::add('general',"Error: Unable to Insert $db_user with permissions to $database on $hostname " . mysql_error());
			return false;
		}
	} // end if we are creating a user

	// Figure out which version of MySQL we're running, if possible we want to use the UTF-8 dump
	$sql = "SELECT version()"; 
	$db_results = @mysql_query($sql,$dbh); 

	$data = mysql_fetch_assoc($db_results,$dbh); 
	$mysql_version = substr(preg_replace("/(\d+)\.(\d+)\.(\d+).*/","$1$2$3",$data[0]),0,3);

	$sql_file =  ($mysql_version < '500') ? 'sql/ampache40.sql' : 'sql/ampache.sql'; 

	/* Attempt to insert database */
         $query = fread(fopen($sql_file, "r"), filesize($sql_file));
         $pieces  = split_sql($query);
         for ($i=0; $i<count($pieces); $i++) {
                 $pieces[$i] = trim($pieces[$i]);
                 if(!empty($pieces[$i]) && $pieces[$i] != "#") {
			   //FIXME: This is for a DB prefix when we get around to it
//                         $pieces[$i] = str_replace( "#__", $DBPrefix, $pieces[$i]);
                         if (!$result = Dba::query ($pieces[$i])) {
                                 $errors[] = array ( mysql_error(), $pieces[$i] );
                         } // end if
                 } // end if
         } // end for

	return true;

} // install_insert_db

/**
 * install_create_config
 * attempts to write out the config file
 * if it can't write it out it will prompt the
 * user to download the config file.
 */
function install_create_config($web_path,$username,$password,$hostname,$database) { 

        $data['database_username'] = $username;
        $data['database_password'] = $password;
        $data['database_hostname'] = $hostname;
        $data['database_name']     = $database;
	$data['web_path']	   = $web_path; 

        Config::set_by_array($data,'1');

        /* Attempt to make DB connection */
        $dbh = Dba::dbh();

	/* 
	  First Test The Variables they've given us to make
	  sure that they actually work!
	*/
	// Connect to the DB
	if(!is_resource($dbh)) { 
		Error::add('general',"Database Connection Failed Check Hostname, Username and Password");
		return false;
	}
	if (!$db_selected = @mysql_select_db($database, $dbh)) { 
		Error::add('general',"Database Selection Failure Check Existance of $database");
		return false;
	}

	$final = generate_config($data); 

	$browser = new Browser(); 
	$browser->downloadHeaders('ampache.cfg.php','text/plain',false,filesize('config/ampache.cfg.php.dist')); 
	echo $final; 
	exit();

	return true;

} // install_create_config

/**
 * install_create_account
 * this creates your initial account and sets up the preferences for the -1 user and you
 */
function install_create_account($username,$password,$password2) { 

	if (!strlen($username) OR !strlen($password)) { 
		Error::add('general',_('No Username/Password specified'));
		return false; 
	}

	if ($password !== $password2) { 
		Error::add('general',_('Passwords do not match'))
		return false; 
	} 

	$dbh = Dba::dbh(); 	

	if (!is_resource($dbh)) { 
		Error::add('general','Database Connection Failed:' . mysql_error());
		return false; 
	}
		
	$db_select = @mysql_select_db(Config::get('database_name'),$dbh);
	
	if (!$db_select) { 
		Error::add('general','Database Select Failed:' . mysql_error());
		return false; 
	}

	$username = Dba::escape($username);
	$password = Dba::escape($password);

	$insert_id = User::create($username,'Administrator','',$password,'100'); 
	
	if (!$insert_id) { 
		Error::add('general',"Insert of Base User Failed " . mysql_error());
		return false; 
	}

	// Fix the system users preferences
	User::fix_preferences('-1');

	return true;
		
} // install_create_account

?>
