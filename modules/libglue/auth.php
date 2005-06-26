<?
/* ------------------- CVS INFO ----------------------
 *
 *      $Source: /data/cvsroot/ampache/libglue/auth.php,v $
 *      last modified by $Author: vollmerk $ at $Date: 2003/11/27 10:19:28 $
 *
 * Libglue, a free php library for handling authentication
 *   and session management.
 *
 * Written and distributed by Oregon State University.
 *   http://oss.oregonstate.edu/libglue
 *
 * ---------------------------------------------------
 */

//
// Attempt to authenticate using the services in
//    auth_methods, and returns an auth_config object
//    which describes the results of the authentication
//    attempt
function authenticate($username, $password)
{
	// First thing to do is check for the gone fishing file:
	$stopfile = libglue_param('stop_auth');
	if ( file_exists($stopfile) )
	{
		echo "We should tell the users here that no one can log in.\n";
		exit();
	}

	$methods = libglue_param('auth_methods');
	if(!is_array($methods))
	{
		$auth = call_user_func("auth_$methods",$username,$password);
	}
	else
	{
		foreach($methods as $method)
		{
			$auth = call_user_func("auth_$method", $username,$password);
			if($auth['success'] == 1) break;
		}
	}
	return $auth;
}

function get_ldap_user ($username,$fields=0)
{
	$auth = array();

	$auth_dn = libglue_param('ldap_auth_dn');
	$user_dn = libglue_param('ldap_user_dn');
	$filter = libglue_param('ldap_filter');
	$host   = libglue_param('ldap_host');
    $pass   = libglue_param('ldap_pass');
	$ldapfields = libglue_param('ldap_fields');
    $protocol = libglue_param('ldap_version');

	// can we even connect?
	if ( $ldap_link = @ldap_connect( $host ) )
	{

        //Snazzy new protocol stuff
        if(!empty($protocol)) ldap_set_option($ldap_link,
                                                LDAP_OPT_PROTOCOL_VERSION,
                                                $protocol);

		// now try and bind with system credentials for searching.
		if ( @ldap_bind($ldap_link, $filter."".$auth_dn, $pass) )
		{
			// now search and retrieve our user data
            $ldap_uid = libglue_param('ldap_uidfield');
            $ldap_username = libglue_param('ldap_usernamefield');

            //force uid and username to be part of the query
            if(!in_array($ldap_uid,$ldapfields)) $ldapfields[] = $ldap_uid;
            if(!in_array($ldap_username,$ldapfields)) $ldapfields[] = $ldap_username;

			$sr = ldap_search($ldap_link, $user_dn, "(".$filter."".$username.")", $ldapfields, 0, 1);
/*			$sr = @ldap_search($ldap_link, $user_dn, "(".$filter."".$username.")");*/

			//info will contain a 1-element array with our user's info
			$info = ldap_get_entries($ldap_link, $sr);

            foreach($ldapfields as $field)
            {
                $auth[$field] = $info[0][$field][0];
            }
            $sess_username = libglue_param('user_username');
            $sess_id = libglue_param('user_id');
			$auth[$sess_username] = $username;
			$auth[$sess_id] = $info[0][$ldap_uid][0];
		}

		//
		// Here means we couldn't use the  service.
		// So it's most likely config related.
		// Check the  username and password?
		//
		else
		{ $auth['error']  = libglue_param('bad_auth_cred'); }
	}

	//
	// This most often will mean we can't reach the  server.
	// Perhaps it's down, or we mistyped the address.
	//
	else
	{ $auth['error']  = libglue_param('connect_error'); }

	// Done with the link, give it back
	ldap_close($ldap_link);

    $auth_methods = libglue_param('auth_methods');
    if(!is_array($auth_methods)) $auth_methods = array($auth_methods);
    if(in_array('sso',$auth_methods,TRUE)) $auth['type'] = 'sso';
    else $auth['type'] = 'ldap';
	return $auth;
}

function get_mysql_user ($username,$fields=null)
{
	$auth = array();
        $dbh = dbh();
        $user_table = libglue_param('mysql_table');
        $mysql_uid = libglue_param('mysql_uidfield');
        $mysql_username = libglue_param('mysql_usernamefield');
        $mysql_fields = libglue_param('mysql_fields');
        $sql = "SELECT ";
        if(is_null($fields)) $sql .= " * ";
        else 
        {
            if(!is_array($fields)) $fields = array($fields);
            foreach($fields as $field)
            {
                $sql .= "$field,";
            }
            $sql = substr($sql, 0, strlen($sql)-1);
        }

        $sql .= " FROM $user_table WHERE $mysql_username = '$username'";
        $result = mysql_query($sql, $dbh);

        foreach($ldapfields as $field)
        {
            $auth[$field] = $info[0][$field][0];
        }
        $sess_username = libglue_param('user_username');
        $sess_id = libglue_param('user_id');
	$auth[$sess_username] = $username;
	$auth[$sess_id] = $info[0][$ldap_uid][0];

    $auth['type'] = 'mysql';
    return $auth;
}


function auth_ldap ($username, $password)
{
    $auth = array();
    $auth['success'] = 0; // don't want to keep setting this
    $auth_dn = libglue_param('ldap_auth_dn');
    $user_dn = libglue_param('ldap_user_dn');
    $filter = libglue_param('ldap_filter');
    $host   = libglue_param('ldap_host');
    $pass   = libglue_param('ldap_pass');
    $ldapfields = libglue_param('ldap_fields');
    // Did we get fed proper variables?
    if(!$username || !$password) 
    {
        $auth['error'] = libglue_param('empty_field');
        // I'm not a huge fan of returning here,
        // but why force more logic?
        return $auth;
    }

    // can we even connect?  
    if ( $ldap_link = @ldap_connect( $host ) ) 
    { 
        // now try and bind with system credentials for searching.  
        if ( @ldap_bind($ldap_link, $filter."".$auth_dn, $pass) )
        { 
            // now search and retrieve our user data 
            $ldap_uid = libglue_param('ldap_uidfield');
            $ldap_username = libglue_param('ldap_usernamefield');

            //force uid and username to be part of the query
            if(!in_array($ldap_uid,$ldapfields)) $ldapfields[] = $ldap_uid;
            if(!in_array($ldap_username,$ldapfields)) $ldapfields[] = $ldap_username;

            $sr = ldap_search($ldap_link, $user_dn, "(".$filter."".$username.")", $ldapfields, 0, 1);
            //info will contain a 1-element array with our user's info
            $info = @ldap_get_entries($ldap_link, $sr);

            //
            // The real authentication:
            // binding here with the user's credentials
            //
            //if ( ldap_bind($ldap_link, $user_dn, $password) ) {
            if ( ($info["count"] == 1) && (@ldap_bind($ldap_link,
                                            $info[0]['dn'],
                                            $password) ) )
            {
                $auth['info'] = array();
                foreach($ldapfields as $field)
                {
                    $auth['info'][$field] = $info[0][$field][0];
                }
                $sess_username = libglue_param('user_username');
                $sess_id = libglue_param('user_id');
                $auth['info'][$sess_username] = $username;
                $auth['info'][$sess_id] = $info[0][$ldap_uid][0];
                $auth['success'] = 1;
            }
            else
            {
                // show the  error here, better than anything I can come up with
                // most likely bad username or password
                // We'll handle two cases, where the username doesn't exist,
                //    and where more than 1 exists separately in case we
                //    decide to do some logging or something fancy someday
                if($info["count"] == 0)
                {
                    $auth['error'] = libglue_param('login_failed');
                }
                else
                {
                    // We could return the  error here
                    // EXCEPT that we want the error message to be the same
                    // for a bad password as a bad username
                    // $auth->error  = ldap_error($ldap_link);
                    $auth['error'] = libglue_param('login_failed');
                }
            }
        }

        //
        // Here means we couldn't use the  service.
        // So it's most likely config related.
        // Check the  username and password?
        //
        else
        { 
            $auth['error']  = libglue_param('bad_auth_cred'); 
        }
    }

    //
    // This most often will mean we can't reach the  server.
    // Perhaps it's down, or we mistyped the address.
    //
    else
    { 
        $auth['error']  = libglue_param('connect_error'); 
    }

    // Done with the link, give it back
    ldap_close($ldap_link);
    $auth['type'] = 'ldap';
    return $auth;
}

/*
 *	MySQL authentication.
 *	returns true/false depending on whether the user was authenticated
 *		successfully
 *	The crypt settings below assume the php crypt() function created the passwords.
 *   But hopson updated it to use mysql PASSWORD() instead
 */

function auth_mysql($username, $password) {

    $auth = array();
    $auth['success'] = 0;

    // Did we get fed proper variables?
    if(!$username or !$password) {
        $auth['error'] = 'Empty username/password';
        return $auth;
    }

	//
	// Retrieve config parameters set in config.php
	//
	$dbhost = libglue_param('mysql_host');
	$dbuser = libglue_param('mysql_user');
	$dbpass = libglue_param('mysql_pass');
	$dbname = libglue_param('mysql_db');
	$passfield = libglue_param('mysql_passcol');
	$table = libglue_param('mysql_table');
	$usercol = libglue_param('mysql_usercol');
	$other = libglue_param('mysql_other');
	$fields = libglue_param('mysql_fields');


    $mysql_uidfield = libglue_param('mysql_uidfield');
    $mysql_usernamefield = libglue_param('mysql_usernamefield');

    if(!preg_match("/$mysql_uidfield/",$fields)) $fields .= ",$mysql_uidfield";
    if(!preg_match("/$mysql_usernamefield/",$fields)) $fields .= ",$mysql_usernamefield";

	if($other == '') $other = '1=1';

	if ($mysql_link = @mysql_connect($dbhost,$dbuser,$dbpass))
	{
		//
		// now retrieve the stored password to use as salt
		// for password checking
		//
		$sql = "SELECT $passfield FROM $table" .
				" WHERE $usercol = '$username' " .
				" AND $other LIMIT 1";
		@mysql_select_db($dbname, $mysql_link);
		$result = @mysql_query($sql, $mysql_link);
		$row = @mysql_fetch_array($result);
		
		$password_check_sql = "PASSWORD('$password')";

		$sql = "SELECT version()";
		$db_results = @mysql_query($sql, $mysql_link);
		$version = @mysql_fetch_array($db_results);

		$mysql_version = substr(preg_replace("/(\d+)\.(\d+)\.(\d+).*/","$1$2$3",$version[0]),0,3);
		
		if ($mysql_version > "409" AND substr($row[0],0,1) !== "*") {
			$password_check_sql = "OLD_PASSWORD('$password')";
		}

		$sql = "SELECT $fields FROM $table" .
			" WHERE $usercol = '$username'" .
            		" AND $passfield = $password_check_sql" .
			" AND $other LIMIT 1";
	$rs = @mysql_query($sql, $mysql_link);
		//This should only fail on a badly formed query.
		if(!$rs)
		{
			$auth['error'] = @mysql_error();
		}

		//
		// Retrieved the right info, set auth->success and info.
		//
		if (@mysql_num_rows($rs) == 1)
		{
            // username and password are successful
            $row = mysql_fetch_array($rs);
            $sess_username = libglue_param('user_username');
            $sess_id = libglue_param('user_id');
            $auth[$info][$sess_username] = $row[$mysql_usernamefield];
            $auth[$info][$sess_id] = $row[$mysql_uidfield];
            $auth[$info] = $row;
            $auth['info'] = $row;
            $auth['success'] = 1;
		}

		//
		// We didn't find anything matching.  No user, bad password, ?
		//
		else
		{
            $auth['error'] = libglue_param('login_failed');
		}
	}

	//
	// Couldn't connect to database at all.
	//
	else
	{
        $auth['error'] = libglue_param('bad_auth_cred');
	}

    	$auth['type'] = 'mysql';
	return $auth;

} // auth_mysql


function auth_sso ($username, $password)
{
    $auth = new auth_response();
    $auth->success = 0;
    $auth->error = "SSO Authentication failed.";
    return $auth;
}

// This is the auth_response class that will be returned during
//   and authentication - this allows us to set some variables
//   by the session for later lookup
class auth_response {
	var $username;
    var $userid;
	var $error;
	var $success;
	var $info;
}


?>
