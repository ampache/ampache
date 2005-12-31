<?php
/* 
 * ---------------------------- CVS INFO --------------------------------
 *
 *     $Source: /data/cvsroot/ampache/libglue/dbh.php,v $
 *     last modified by $Author: vollmerk $ at $Date: 2003/11/24 05:53:13 $
 *
 *     Libglue, a free php library for handling authentication 
 *       and session management. 
 *
 *     Written and distributed by Oregon State University.
 *       http://oss.oregonstate.edu/libglue
 *
 * -----------------------------------------------------------------------
 */
 
/*---------------------------------------------------------------------- 

 For complete information on this toolkit see the README located in this
   directory.

 This is the database handler class.  This will setup and return a
   database handle for use in your application.  Simply pass it a
   username and password.  If an error occurs you'll be presented with
   a verbose reason for the error.
----------------------------------------------------------------------*/ 

function setup_sess_db($name, $host, $db, $username, $password) 
{
	$dbh = mysql_connect($host, $username, $password) or header("Location:" . conf('web_path') . "/test.php");
	if ( !is_resource($dbh) )
	{
		echo "Unable to connect to \"". $host ."\" in order to \n" .   
     		"use the \"". $db ."\" database with account \"".$username." : ".$password.
			"\"\n . Perhaps the database is not " .
         	"running, \nor perhaps the admin needs to change a few variables in\n ".
         	"the config files in order to point to the correct database.\n";
  		echo "Details: " .
           		mysql_errno() . ": " .
           		mysql_error() . "\n";
        die();
	}

	else 
	{
		@mysql_select_db($db, $dbh) or header("Location:" . conf('web_path') . "/test.php");
		libglue_param(array($name=>$dbh));
	}

	return $dbh;
}

?>
