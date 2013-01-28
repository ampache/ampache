<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 *
 * This class handles all of the session related stuff in Ampache
 *
 */
class Session {

    /**
     * Constructor
     * This should never be called
     */
    private function __construct() {
        // Rien a faire
    } // __construct

    /**
     * open
     *
     * This function is for opening a new session so we just verify that we
     * have a database connection, nothing more is needed.
     */
    public static function open($save_path, $session_name) {
        if (!is_resource(Dba::dbh())) {
            debug_event('session', 'Error: no database connection session failed', 1);
            return false;
        }

        return true;
    }

    /**
     * close
     *
     * This is run on the end of a session, nothing to do here for now.
     */
    public static function close() {
        return true;
    }

    /**
     * write
     *
     * This saves the session information into the database.
     */
    public static function write($key, $value) {
        if (defined('NO_SESSION_UPDATE')) { return true; }

        $length = Config::get('session_length');
        $value = Dba::escape($value);
        $key = Dba::escape($key);
        // Check to see if remember me cookie is set, if so use remember
        // length, otherwise use the session length
        $expire = isset($_COOKIE[Config::get('session_name') . '_remember']) 
            ? time() + Config::get('remember_length') 
            : time() + Config::get('session_length');

        $sql = "UPDATE `session` SET `value`='$value', " .
            "`expire`='$expire' WHERE `id`='$key'";
        $db_results = Dba::read($sql);

        debug_event('session', 'Writing to ' . $key . ' with expire ' . $expire . ' ' . Dba::error(), 6);

        return $db_results;
    }

    /**
     * destroy
     *
     * This removes the specified session from the database.
     */
    public static function destroy($key) {
        $key = Dba::escape($key);

        if (!strlen($key)) { return false; }

        // Remove anything and EVERYTHING
        $sql = "DELETE FROM `session` WHERE `id`='$key'";
        $db_results = Dba::write($sql);

        debug_event('SESSION', 'Deleting Session with key:' . $key, '6');

        // Destroy our cookie!
        setcookie(Config::get('session_name'), '', time() - 86400);

        return true;
    }

    /**
     * gc
     *
     * This function is randomly called and it cleans up the spoo
     */
    public static function gc($maxlifetime) {

        $sql = "DELETE FROM `session` WHERE `expire` < '" . time() . "'";
        $db_results = Dba::write($sql);

        // Also clean up things that use sessions as keys
        Query::gc();
        Tmp_Playlist::gc();

        return true;
    }

    /**
     * read
     *
     * This takes a key and returns the data from the database.
     */
    public static function read($key) {
        return self::_read($key, 'value');
    }

    /**
     * _read
     *
     * This returns the specified column from the session row.
     */
    private static function _read($key, $column) {
        $key = Dba::escape($key);

        $sql = "SELECT * FROM `session` WHERE `id`='$key' AND `expire` > '" . time() . "'";
        $db_results = Dba::read($sql);

        if ($results = Dba::fetch_assoc($db_results)) {
            return $results[$column];
        }
        
        debug_event('session', 'Unable to read session from key ' . $key . ' no data found', 5);

        return '';
    }

    /**
     * username
     *
     * This returns the username associated with a session ID, if any
     */
    public static function username($key) {
        return self::_read($key, 'user');
    }

    /**
     * create
     * This is called when you want to create a new session
     * it takes care of setting the initial cookie, and inserting the first
     * chunk of data, nifty ain't it!
     */
    public static function create($data) {

        // Regenerate the session ID to prevent fixation
        switch ($data['type']) {
            case 'xml-rpc':
            case 'api':
                $key = md5(uniqid(rand(), true));
            break;
            case 'mysql':
            default:
                session_regenerate_id();

                // Before refresh we don't have the cookie so we
                // have to use session ID
                $key = session_id();
            break;
        } // end switch on data type

        $username = Dba::escape($data['username']);
        $ip = $_SERVER['REMOTE_ADDR'] 
            ? Dba::escape(inet_pton($_SERVER['REMOTE_ADDR'])) 
            : '0';
        $type = Dba::escape($data['type']);
        $value = Dba::escape($data['value']);
        $agent = Dba::escape(substr($_SERVER['HTTP_USER_AGENT'], 0, 254));
        $expire = Dba::escape(time() + Config::get('session_length'));

        if (!strlen($value)) { $value = ' '; }

        /* Insert the row */
        $sql = "INSERT INTO `session` (`id`,`username`,`ip`,`type`,`agent`,`value`,`expire`) " .
            " VALUES ('$key','$username','$ip','$type','$agent','$value','$expire')";
        $db_results = Dba::write($sql);

        if (!$db_results) {
            debug_event('session', 'Session creation failed', 1);
            return false;
        }

        debug_event('session', 'Session created:' . $key, 5);

        return $key;
    }

    /**
     * check
     *
     * This checks for an existing session. If it's still valid we go ahead
     * and start it and return true.
     */
    public static function check() {

        $session_name = Config::get('session_name');

        // No cookie no go!
        if (!isset($_COOKIE[$session_name])) { return false; }

        // Check for a remember me
        if (isset($_COOKIE[$session_name . '_remember'])) {
            self::create_remember_cookie();
        }

        // Set up the cookie params before we start the session.
        // This is vital
        session_set_cookie_params(
            Config::get('cookie_life'),
            Config::get('cookie_path'),
            Config::get('cookie_domain'),
            Config::get('cookie_secure'));

        // Set name
        session_name($session_name);

        // Ungimp IE and go
        self::ungimp_ie();
        session_start();

        return true;
    }

    /**
     * exists
     *
     * This checks to see if the specified session of the specified type
     * exists, it also provides an array of keyed data that may be required
     * based on the type.
     */
    public static function exists($type, $key, $data=array()) {
        // Switch on the type they pass
        switch ($type) {
            case 'xml-rpc':
            case 'api':
                $key = Dba::escape($key);
                $time = time();
                $sql = "SELECT * FROM `session` WHERE " .
                    "`id`='$key' AND `expire` > '$time' " .
                    "AND `type`='$type'";
                $db_results = Dba::read($sql);

                if (Dba::num_rows($db_results)) {
                    return true;
                }
            break;
            case 'interface':
                $key = Dba::escape($key);
                $time = time();
                // Build a list of enabled authentication types
                $types = Config::get('auth_methods');
                if (!Config::get('use_auth')) {
                    $types[] = '';
                }
                $enabled_types = implode("','", $types);
                $sql = "SELECT * FROM `session` WHERE " .
                    "`id`='$key' AND `expire` > '$time' " .
                    "AND `type` IN('$enabled_types')"; 
                $db_results = Dba::read($sql);

                if (Dba::num_rows($db_results)) {
                    return true;
                }
            break;
            case 'stream':
                $key    = Dba::escape($key);
                $ip    = Dba::escape(inet_pton($data['ip']));
                $agent    = Dba::escape($data['agent']);
                $sql = "SELECT * FROM `session_stream` WHERE " .
                    "`id`='$key' AND `expire` > '$time' " .
                    "AND `ip`='$ip' AND `agent`='$agent'";
                $db_results = Dba::read($sql);

                if (Dba::num_rows($db_results)) {
                    return true;
                }

            break;
            default:
                return false;
            break;
        } // type

        // Default to false
        return false;

    }

    /**
     * extend
     *
     * This takes a SID and extends its expiration.
     */
    public static function extend($sid) {
        $time = time();
        $sid = Dba::escape($sid);
        $expire = isset($_COOKIE[Config::get('session_name') . '_remember']) 
            ? $time + Config::get('remember_length') 
            : $time + Config::get('session_length');

        $sql = "UPDATE `session` SET `expire`='$expire' WHERE `id`='$sid'";
        if ($db_results = Dba::write($sql)) {
            debug_event('session', $sid . ' has been extended to ' . date('r', $expire) . ' extension length ' . ($expire - $time), 5);
        }

        return $db_results;
    }

    /**
     * _auto_init
     * This function is called when the object is included, this sets up the
     * session_save_handler
     */
    public static function _auto_init() {

        if (!function_exists('session_start')) {
            header("Location:" . Config::get('web_path') . "/test.php");
            exit;
        }

        session_set_save_handler(
            array('Session', 'open'),
            array('Session', 'close'),
            array('Session', 'read'),
            array('Session', 'write'),
            array('Session', 'destroy'),
            array('Session', 'gc'));

    }

    /**
     * create_cookie
     *
     * This is separated into its own function because of some flaws in
     * specific webservers *cough* IIS *cough* which prevent us from setting
     * a cookie at the same time as a header redirect. As such on view of a
     * login a cookie is set with the proper name
     */
    public static function create_cookie() {
        // Set up the cookie prefs before we throw down, this is very important
        $cookie_life    = Config::get('cookie_life');
        $cookie_path    = Config::get('cookie_path');
        $cookie_domain    = false;
        $cookie_secure    = Config::get('cookie_secure');

        session_set_cookie_params($cookie_life,$cookie_path,$cookie_domain,$cookie_secure);

        session_name(Config::get('session_name'));

        /* Start the session */
        self::ungimp_ie();
        session_start();
    }

    /**
     * create_remember_cookie
     *
     * This function just creates the remember me cookie, nothing special
     */
    public static function create_remember_cookie() {

        $remember_length = Config::get('remember_length');
        $session_name = Config::get('session_name');

        Config::set('cookie_life', $remember_length, true);
        setcookie($session_name . '_remember',"Rappelez-vous, rappelez-vous le 27 mars", time() + $remember_length, '/');  

    }

    /**
     * ungimp_ie
     * This function sets the cache limiting to public if you are running
     * some flavor of IE. The detection used here is very conservative so
     * feel free to fix it. This only has to be done if we're rolling HTTPS.
     */
    public static function ungimp_ie() {

        // If no https, no ungimpage required
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'on') {
            return true;
        }

        // Try to detect IE
        $agent = trim($_SERVER['HTTP_USER_AGENT']);

        if ((strpos($agent, 'MSIE') !== false) ||
            (strpos($agent,'Internet Explorer/') !== false)) {
            session_cache_limiter('public');
        }

        return true;

    } // ungimp_ie

} 
?>
