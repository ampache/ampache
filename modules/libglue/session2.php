<?php
  require_once('libdb.php');

function libglue_sess_db($dbtype = 'local')
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
    $dbh = db_connect($host,$user,$pass);
    db_select($db);
    libglue_param(array($dbh_name=>$dbh));

    if(is_resource($dbh)) return $dbh;
    else die("Could not connect to $dbtype database for session management");
}

/* This function is public */
function check_session($id=null)
{
    if(is_null($id))
    {
        //From Karl Vollmer, vollmerk@net.orst.edu:
        //  naming the session and starting it is sufficient
        //  to retrieve the cookie
        $name = libglue_param('sess_name');
        if(!empty($name)) session_name($name);
        session_start();
        $id = strip_tags($_COOKIE[$name]);
    }

    // Now what we have a session id, let's verify it:
    if(libglue_sso_mode())
    {
        // if sso mode, we must have a valid sso session already
        $sso_sess = libglue_sso_check($id);
        if(!is_null($sso_sess))
        {
            // if sso is valid, it's okay to create a new local session
            if($local_sess = libglue_local_check($id))
            {
                return true;
            }
            else
            {
                libglue_local_create($id,
                                      $sso_sess[libglue_param('sso_username_col')],
                                      'sso',
                                      $sso_sess[libglue_param('sso_expire_col')]);
                return true;
            }
        }
        else
        // libglue_sso_check failed
        {
            libglue_sess_destroy($id);
            return false;
        }
    }
    else
    {
        //if not in sso mode, there must be a local session
        if($local_sess = libglue_local_check($id))
        {
            return true;
        }
        else
        {
            //you're gone buddy
            libglue_sess_destroy($id);
            return false;
        }
    }
}

// private function, don't ever use this:
function libglue_sso_mode()
{
    $auth_methods = libglue_param('auth_methods');
    if(!is_array($auth_methods)) $auth_methods = array($auth_methods);
    return (in_array('sso',$auth_methods))?true:false;
}

function libglue_sso_check($sess_id)
{
    // Read the sso info from the config file:
    $sso_table = libglue_param('sso_table');
    $sso_sid = libglue_param('sso_sessid_col');
    $sso_expire_col = libglue_param('sso_expire_col');
    $sso_length = libglue_param('sso_length');
    $sso_username_col = libglue_param('sso_username_col');

    $sso_dbh = libglue_sess_db('sso');
    $sql = "SELECT * FROM $sso_table WHERE $sso_sid='$sess_id' AND $sso_expire_col > UNIX_TIMESTAMP()";
    $db_result = db_query($sql, $sso_dbh);
    if(is_resource($db_result)) $sso_session = db_fetch($db_result);
    else $sso_session = null;

    $retval = (is_array($sso_session))?$sso_session:null;
    return $retval;
}

function libglue_local_check($sess_id)
{
    static $retval = -1;
    if($retval != -1) return $retval;

    $local_table = libglue_param('local_table');
    $local_sid = libglue_param('local_sid');
    $local_expirecol = libglue_param('local_expirecol');
    $local_length = libglue_param('local_length');
    $local_usercol = libglue_param('local_usercol');
    $local_datacol = libglue_param('local_datacol');
    $local_typecol = libglue_param('local_typecol');

    $local_dbh = libglue_sess_db('local');
    $sql = "SELECT $local_datacol FROM $local_table WHERE $local_sid='$sess_id' AND $local_expirecol > UNIX_TIMESTAMP()";
    $db_result = db_query($sql, $local_dbh);
    if(is_resource($db_result)) $session = db_fetch($db_result);
    else $session = null;

    if(is_array($session))
    {
        $retval = $session[$local_datacol];
    }
    else $retval = null;
    return $retval;
}

function libglue_local_create($sess_id, $username, $type, $expire)
{
    if($type === "sso" || $type === "ldap")
        $userdata = get_ldap_user($username);
    else if($type === "mysql")
        $userdata = get_mysql_user($username);

    $data = array(libglue_param('user_data_name')=>$userdata);

    // It seems we have to set $_SESSION manually, or it gets blasted
    //  by php's session write handler
    $_SESSION = $data;
    $db_data = serialize($data);
    $local_dbh = libglue_sess_db('local');

    // Local parameters we need:
    $local_table = libglue_param('local_table');
    $local_sid = libglue_param('local_sid');
    $local_usercol = libglue_param('local_usercol');
    $local_datacol = libglue_param('local_datacol');
    $local_expirecol = libglue_param('local_expirecol');
    $local_typecol = libglue_param('local_typecol');

    // session data will be saved when the script terminates,
    //   but not the rest of this fancy info
    $sql= "INSERT INTO $local_table ".
          " ($local_sid,$local_usercol,$local_datacol,$local_expirecol,$local_typecol)".
          " VALUES ('$sess_id','$username','$db_data','$expire','$type')";
    $db_result = db_query($sql, $local_dbh);
    if(!$db_result) die("Died trying to create local session: <pre><br>$sql</pre>");
}

function sess_open()
{
    if(libglue_sso_mode())
    {
        if(!is_resource(libglue_sess_db('sso')))
        {
            die("<!-- Unable to connect to the SSO server in order to " .
             "use the session database. Perhaps the database is not ".
             "running, or perhaps the admin needs to change a few variables in ".
             "modules/include/global_settings in order to point to the correct ".
             "database.-->\n");
            return false;
        }
    }

    if(!is_resource(libglue_sess_db('local')))
    {
        die("<!-- Unable to connect to local server in order to " .
             "use the session database. Perhaps the database is not ".
             "running, or perhaps the admin needs to change a few variables in ".
             "the config file in order to point to the correct ".
             "database.-->\n");
        return false;
    }
    return true;
}

function sess_close(){ return true; }

function sess_write($sess_id, $sess_data)
{
    $local_dbh = libglue_sess_db('local');
    $local_datacol = libglue_param('local_datacol');
    $local_table = libglue_param('local_table');
    $local_sid = libglue_param('local_sid');

    $auth_methods = libglue_param('auth_methods');
    $local_expire = libglue_param('local_expirecol');
    $local_length = libglue_param('local_length');
    $time = $local_length+time();

    // If not using sso, we now need to update the expire time
    $local_expire = libglue_param('local_expirecol');
    $local_length = libglue_param('local_length');
    $time = $local_length+time();
    $sql = "UPDATE $local_table SET $local_datacol='$sess_data',$local_expire='$time'".
           " WHERE $local_sid = '$sess_id'";
    db_query($sql, $local_dbh);

    if(libglue_sso_mode())
    {
        $sso_table = libglue_param('sso_table');
        $sso_expire_col = libglue_param('sso_expire_col');
        $sso_sess_length = libglue_param('sso_length_col');
        $sso_sess_id = libglue_param('sso_sessid_col');
        $time = time();
        $sql = "UPDATE $sso_table SET $sso_expire_col = $sso_sess_length + UNIX_TIMESTAMP() WHERE $sso_sess_id = '$sess_id'";
        $sso_dbh = libglue_sess_db('sso');
        db_query($sql, $sso_dbh);
    }
    return true;
}

//
// This function is called with random frequency
//   to remove expired session data
//
function sess_gc($maxlifetime)
{
    if(libglue_sso_mode())
    {
        //Delete old sessions from SSO
        // We do 'where length' so we don't accidentally blast
        //  another app's sessions
        $sso_expirecol = libglue_param('sso_expire_col');
        $sso_table = libglue_param('sso_table');
        $sso_length = libglue_param('sso_length_col');
        $local_length = libglue_param('local_length');

        $sso_dbh = libglue_sess_db('sso');
        $time = time();
        $sql = "DELETE FROM $sso_table WHERE $sso_expirecol < $time".
               " AND $sso_length = '$local_length'";
        $result = db_query($sql, $sso_dbh);
    }
    $local_expire = libglue_param('local_expire');
    $local_table = libglue_param('local_table');
    $time = time();
    $local_dbh = libglue_sess_db('local');
    $sql = "DELETE FROM $local_table WHERE $local_expire < $time";
    $result = db_query($sql, $local_dbh);
    return true;
}

function libglue_sess_destroy($id=null)
{
    if(is_null($id))
    {
        //From Karl Vollmer, vollmerk@net.orst.edu:
        //  naming the session and starting it is sufficient
        //  to retrieve the cookie
        $name = libglue_param('sess_name');
        if(!empty($name)) session_name($name);
        session_start();
        $id = strip_tags($_COOKIE[$name]);
    }
    if(libglue_sso_mode())
    {
        $sso_sid = libglue_param('sso_sessid_col');
        $sso_table = libglue_param('sso_table');
        $sso_dbh = libglue_sess_db('sso');
        $sql = "DELETE FROM $sso_table WHERE $sso_sid = '$id' LIMIT 1";
        $result = db_query($sql, $sso_dbh);
    }
    $local_sid = libglue_param('local_sid');
    $local_table = libglue_param('local_table');

    $local_dbh = libglue_sess_db('local');
    $sql = "DELETE FROM $local_table WHERE $local_sid = '$id' LIMIT 1";
    $result = db_query($sql, $local_dbh);

    // It is very important we destroy our current session cookie,
    //   because if we don't, a person won't be able to log in again
    //   without closing their browser - SSO doesn't respect
    //
    //   Code from http://php.oregonstate.edu/manual/en/function.session-destroy.php,
    //          Written by powerlord@spamless.vgmusic.com,  18-Nov-2002 08:41
    //
    $cookie = session_get_cookie_params();
    if ((empty($cookie['domain'])) && (empty($cookie['secure'])) ) 
    {
        setcookie(session_name(), '', time()-3600, $cookie['path']);
    } elseif (empty($CookieInfo['secure'])) {
        setcookie(session_name(), '', time()-3600, $cookie['path'], $cookie['domain']);
    } else {
        setcookie(session_name(), '', time()-3600, $cookie['path'], $cookie['domain'], $cookie['secure']);
    }
    // end powerloard

    unset($_SESSION);
    unset($_COOKIE[session_name()]);

    return TRUE;
}

function logout($id=null)
{
    libglue_sess_destroy($id);
    $login_page = libglue_param('login_page');
    header("Location: $login_page");
    die();
    return true; //because why not?
}


//
// Register all our cool session handling functions
//
session_set_save_handler(
    "sess_open",
    "sess_close",
    "libglue_local_check",
    "sess_write",
    "libglue_sess_destroy",
    "sess_gc");

?>
