<?php
/* ------------------- CVS INFO ----------------------
 *
 *	$Source: /data/cvsroot/ampache/libglue/session.php,v $
 *	last modified by $Author: vollmerk $ at $Date: 2003/11/24 05:53:13 $
 *
 * Libglue, a free php library for handling authentication
 *   and session management.
 *
 * Written and distributed by Oregon State University.
 *   http://oss.oregonstate.edu/libglue
 *
 * ---------------------------------------------------
 */


function check_sess_db($dbtype = 'local')
{
    if($dbtype === 'sso')
    {
        $dbh = libglue_param(libglue_param('sso_dbh_name'));
        if(is_resource($dbh)) return $dbh;
        $dbh_name = libglue_param('sso_dbh_name');
        $host = libglue_param('sso_host');
        $db = libglue_param('sso_db');
        $user = libglue_param('sso_username');
        $pass = libglue_param('sso_pass');
        $name = libglue_param('sso_dbh_name');
    }
    elseif($dbtype === 'local')
    {
        $dbh = libglue_param(libglue_param('local_dbh_name'));
        if(is_resource($dbh)) return $dbh;
        $dbh_name = libglue_param('local_dbh_name');
        $host = libglue_param('local_host');
        $db = libglue_param('local_db');
        $user = libglue_param('local_username');
        $pass = libglue_param('local_pass');
        $name = libglue_param('local_dhb_name');
    }
   	$dbh = setup_sess_db($dbh_name,$host,$db,$user,$pass);

    if(is_resource($dbh)) return $dbh;
    else die("Could not connect to $dbtype database for session management");
}

//
// Really we are just checking the session here -- we want to see if

//  if the user has a valid session, if they do then we'll let them do
//  what they need to do.
//

function check_session($id=0)
{
    //If an id isn't passed in, retrieve one from the cookie
	if($id===0) {

		/* 
		  We don't need to set cookie params here php
		  is smart enough to know which cookie it wants
		  via the session_name. Setting cookie params
		  here sometimes made php create a new cookie
		  which is very bad :) -- Vollmer
		*/
		$name = libglue_param('sess_name');
		if($name) session_name($name);

	        // Start the session, then get the cookie id
		session_start();
	        $id = strip_tags($_COOKIE[$name]);
    }
    
    // Determine if we need to check the SSO database:
    $auth_methods = libglue_param('auth_methods');
    if(!is_array($auth_methods)) $auth_methods = array($auth_methods);
    $sso_mode = in_array('sso',$auth_methods,TRUE);

    $local = get_local_session($id);
    if($sso_mode) $sso = get_sso_session($id);

    if($sso_mode && !$sso)
    {
        return FALSE;
    }
    else if ($sso_mode && is_array($sso))
    {
        if(is_array($local)) return TRUE;
        else
        {
            //
            // Should we do gc here, just in case
            // local is only expired?
            // (The insert in make_local_session
            //  will fail if we don't)
            //
            $newlocal = make_local_session_sso($sso);
            return $newlocal;
        }
    }
    //If we get here, we're not using SSO mode
    else if (!is_array($local))
    {
        return FALSE;
    }
    else return TRUE;
}

function make_local_session_only($data,$id=0)
{
    if($id===0)
    {
        $name = libglue_param('sess_name');
        $domain = libglue_param('sess_domain');
        if($name) session_name($name);
        //Lifetime of the cookie:
        $cookielife = libglue_param('sess_cookielife');
        if(empty($cookielife)) $cookielife = 0;
        //Secure cookie?
        $cookiesecure = libglue_param('sess_cookiesecure');
        if(empty($cookiesecure)) $cookiesecure = 0;
        //Cookie path:
        $cookiepath = libglue_param('sess_cookiepath');
        if(empty($cookiepath)) $cookiepath = '/';

        if(!empty($domain)) session_set_cookie_params($cookielife,$cookiepath,$domain,$cookiesecure);
        
	// Start the session
	session_start();


	/*
	  Before a refresh we do not have a cookie value
	  here so let's use session_id() --Vollmer
	*/
        $id = session_id();
    }

    $userfield = libglue_param('user_username');
    $username = $data['info'][$userfield];
    $type = $data['type'];

    $local_dbh = check_sess_db('local');
    $local_table = libglue_param('local_table');
    $local_sid = libglue_param('local_sid');
    $local_usercol = libglue_param('local_usercol');
    $local_datacol = libglue_param('local_datacol');
    $local_expirecol = libglue_param('local_expirecol');
    $local_typecol = libglue_param('local_typecol');
    $sql= "INSERT INTO $local_table ".
          " ($local_sid,$local_usercol,$local_typecol)".
          " VALUES ('$id','$username','$type')";
    $db_result = mysql_query($sql, $local_dbh);

    if($db_result) return TRUE;
    else return FALSE;
}

function make_local_session_sso($sso_session)
{
    $sso_usercol = $sso_session[libglue_param('sso_usercol')];
    $sso_sid = $sso_session[libglue_param('sso_sid')];
    $sso_expire = $sso_session[libglue_param('sso_expirecol')];

    $user = get_ldap_user($sso_usercol);

    $data = array('user'=>$user);

    //Somewhat stupidly, we have to initialize $_SESSION here,
    //  or sess_write will blast it for us
    $_SESSION = $data;

    $db_data = serialize($data);
    $local_dbh = check_sess_db('local');

    //Local stuff we need:
    $local_table = libglue_param('local_table');
    $local_sid = libglue_param('local_sid');
    $local_usercol = libglue_param('local_usercol');
    $local_datacol = libglue_param('local_datacol');
    $local_expirecol = libglue_param('local_expirecol');
    $local_typecol = libglue_param('local_typecol');
    $sql= "INSERT INTO $local_table ".
          " ($local_sid,$local_usercol,$local_datacol,$local_expirecol,$local_typecol)".
          " VALUES ('$sso_sid','$sso_usercol','$db_data','$sso_expire','sso')";
    $db_result = mysql_query($sql, $local_dbh);

    if($db_result) return TRUE;
    else return FALSE;
}

function get_local_session($sid)
{
    $local_table = libglue_param('local_table');
    $local_sid = libglue_param('local_sid');
    $local_expirecol = libglue_param('local_expirecol');
    $local_length = libglue_param('local_length');
    $local_usercol = libglue_param('local_usercol');
    $local_datacol = libglue_param('local_datacol');
    $local_typecol = libglue_param('local_typecol');

    $local_dbh = check_sess_db('local');
    $time = time();
    $sql = "SELECT * FROM $local_table WHERE $local_sid='$sid' AND $local_expirecol > $time";
    $db_result = mysql_query($sql, $local_dbh);
    $session = mysql_fetch_array($db_result);

    if(is_array($session)) $retval = $session;
    else $retval = FALSE;

    if($retval === FALSE)
    {
        //Find out what's going on
    }

    return $retval;
}

function get_sso_session($sid)
{
    $sso_table = libglue_param('sso_table');
    $sso_sid = libglue_param('sso_sid');
    $sso_expirecol = libglue_param('sso_expirecol');
    $sso_length = libglue_param('sso_length');
    $sso_usercol = libglue_param('sso_usercol');

    $sso_dbh = check_sess_db('sso');
    $time = time();
    $sql = "SELECT * FROM $sso_table WHERE $sso_sid='$sid' AND $sso_expirecol > $time";
    $db_result = mysql_query($sql, $sso_dbh);
    $sso_session = mysql_fetch_array($db_result);

    $retval = (is_array($sso_session))?$sso_session:FALSE;
    return $retval;
}



// This will start the session tools, then destroy anything in the database then
//   clear all of the session information
function logout ($id=0)
{
    sess_destroy($id);
    $login_page = libglue_param('login_page');
    // should clear both the database information as well as the
    //   current session info
    header("Location: $login_page");
    die();
    return true;
}

// Double checks that we have a database handle
// Args are completely ignored - we're using a database here
function sess_open($save_path, $session_name)
{
    $local_dbh = check_sess_db();
    if ( !is_resource($local_dbh) )
    {
        echo "<!-- Unable to connect to local server in order to " .
             "use the session database. Perhaps the database is not ".
             "running, or perhaps the admin needs to change a few variables in ".
             "the config file in order to point to the correct ".
             "database.-->\n";
        return FALSE;
    }

    $auth_methods = libglue_param('auth_methods');
    if(!is_array($auth_methods)) $auth_methods = array($auth_methods);
    if(in_array('sso',$auth_methods,TRUE))
    {
        $sso_dbh = check_sess_db('sso');
        if ( !is_resource($sso_dbh) )
        {
            echo "<!-- Unable to connect to the SSO server in order to " .
             "use the session database. Perhaps the database is not ".
             "running, or perhaps the admin needs to change a few variables in ".
             "modules/include/global_settings in order to point to the correct ".
             "database.-->\n";
            return FALSE;
        }
    }
    return TRUE;
}

// Placeholder function, does nothing
function sess_close()
{
    return true;
}

// Retrieve session identified by 'key' from the database
//   and return the data field
function sess_read($key)
{
    $retval = 0;
    $session = get_local_session($key);
    $datacol = libglue_param('local_datacol');
    if(is_array($session)) $retval = $session[$datacol];
    else $retval = "";
    return $retval;
}


//
// Save the session data $val to the database
//
function sess_write($key, $val)
{
    $local_dbh = check_sess_db('local');
    $local_datacol = libglue_param('local_datacol');
    $local_table = libglue_param('local_table');
    $local_sid = libglue_param('local_sid');

    $auth_methods = libglue_param('auth_methods');
    $local_expirecol = libglue_param('local_expirecol');
    $local_length = libglue_param('local_length');
    $time = $local_length+time();
    
    // If they've got the long session
    if ($_COOKIE['amp_longsess'] == '1') { 
	$time = time() + 86400*364;
    }
    
    if(!is_array($auth_methods)) $auth_methods = array($auth_methods);
    if(!in_array('sso',$auth_methods,TRUE))
    {
        // If not using sso, we now need to update the expire time
        $sql = "UPDATE $local_table SET $local_datacol='" . sql_escape($val) . "',$local_expirecol='$time'".
               " WHERE $local_sid = '$key'";
    }
    else $sql = "UPDATE $local_table SET $local_datacol='" . sql_escape($val) . "',$local_expirecol='$time'".
                " WHERE $local_sid = '$key'";

    return mysql_query($sql, $local_dbh);
}

//
// Remove the current session from the database.
//
function sess_destroy($id=0)
{
    if($id == 0) {
        session_start();
    	$id = session_id();
    }

    $auth_methods = libglue_param('auth_methods');
    if(!is_array($auth_methods)) $auth_methods = array($auth_methods);
    if(in_array('sso',$auth_methods,TRUE))
    {
        $sso_sid = libglue_param('sso_sid');
        $sso_table = libglue_param('sso_table');
        $sso_dbh = check_sess_db('sso');
        $sql = "DELETE FROM $sso_table WHERE $sso_sid = '$id' LIMIT 1";
        $result = mysql_query($sql, $sso_dbh);
    }
    $local_sid = libglue_param('local_sid');
    $local_table = libglue_param('local_table');

    $local_dbh = check_sess_db('local');
    $sql = "DELETE FROM $local_table WHERE $local_sid = '$id' LIMIT 1";
    $result = mysql_query($sql, $local_dbh);
    $_SESSION = array();

    /* Delete the long ampache session cookie */
    setcookie ("amp_longsess", "", time() - 3600);

    /* Delete the ampache cookie as well... */
    setcookie (libglue_param('sess_name'),"", time() - 3600);
    
    return TRUE;
}

//
// This function is called with random frequency
//   to remove expired session data
//
function sess_gc($maxlifetime)
{
    $auth_methods = libglue_param('auth_methods');
    if(!is_array($auth_methods)) $auth_methods = array($auth_methods);
    if(in_array('sso',$auth_methods,TRUE))
    {
        //Delete old sessions from SSO
        // We do 'where length' so we don't accidentally blast
        //  another app's sessions
        $sso_expirecol = libglue_param('sso_expirecol');
        $sso_table = libglue_param('sso_table');
        $sso_length = libglue_param('sso_length');
        $local_length = libglue_param('local_length');

        $sso_dbh = check_sess_db('sso');
        $time = time();
        $sql = "DELETE FROM $sso_table WHERE $sso_expirecol < $time".
               " AND $sso_length = '$local_length'";
        $result = mysql_query($sql, $sso_dbh);
    }
    $local_expirecol = libglue_param('local_expirecol');
    $local_table = libglue_param('local_table');
    $time = time();
    $local_dbh = check_sess_db('local');
    $sql = "DELETE FROM $local_table WHERE $local_expirecol < $time";
    $result = mysql_query($sql, $local_dbh);
    return true;
}

//
// Register all our cool session handling functions
//
session_set_save_handler(
	"sess_open",
	"sess_close",
	"sess_read",
	"sess_write",
	"sess_destroy",
	"sess_gc");
?>
