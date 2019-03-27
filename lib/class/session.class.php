<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 *
 * This class handles all of the session related stuff in Ampache
 *
 */
class Session
{
    /**
     * Constructor
     * This should never be called
     */
    private function __construct()
    {
        // Rien a faire
    } // __construct

    /**
     * open
     *
     * This is run on the beginning of a session, nothing to do here for now.
     */
    public static function open()
    {
        return true;
    }

    /**
     * close
     *
     * This is run on the end of a session, nothing to do here for now.
     */
    public static function close()
    {
        return true;
    }

    /**
     * write
     *
     * This saves the session information into the database.
     */
    public static function write($key, $value)
    {
        if (defined('NO_SESSION_UPDATE')) {
            return true;
        }

        $expire = time() + AmpConfig::get('session_length');
        $sql    = 'UPDATE `session` SET `value` = ?, `expire` = ? WHERE `id` = ?';
        Dba::write($sql, array($value, $expire, $key));

        debug_event('session', 'Writing to ' . $key . ' with expiration ' . $expire, 6);

        return true;
    }

    /**
     * destroy
     *
     * This removes the specified session from the database.
     */
    public static function destroy($key)
    {
        if (!strlen($key)) {
            return false;
        }

        // Remove anything and EVERYTHING
        $sql = 'DELETE FROM `session` WHERE `id` = ?';
        Dba::write($sql, array($key));

        debug_event('SESSION', 'Deleting Session with key:' . $key, 6);

        $session_name  = AmpConfig::get('session_name');
        $cookie_path   = AmpConfig::get('cookie_path');
        $cookie_domain = null;
        $cookie_secure = AmpConfig::get('cookie_secure');

        // Destroy our cookie!
        setcookie($session_name, null, -1, $cookie_path, $cookie_domain, $cookie_secure);
        setcookie($session_name . '_user', null, -1, $cookie_path, $cookie_domain, $cookie_secure);
        setcookie($session_name . '_lang', null, -1, $cookie_path, $cookie_domain, $cookie_secure);

        return true;
    }

    /**
     * gc
     *
     * This function is randomly called and it cleans up the spoo
     */
    public static function gc()
    {
        $sql = 'DELETE FROM `session` WHERE `expire` < ?';
        Dba::write($sql, array(time()));

        $sql = 'DELETE FROM `session_remember` WHERE `expire` < ?';
        Dba::write($sql, array(time()));

        // Also clean up things that use sessions as keys
        Query::gc();
        Tmp_Playlist::gc();
        Stream_Playlist::gc();
        Song_Preview::gc();

        return true;
    }

    /**
     * read
     *
     * This takes a key and returns the data from the database.
     */
    public static function read($key)
    {
        return self::_read($key, 'value');
    }

    /**
     * _read
     *
     * This returns the specified column from the session row.
     */
    private static function _read($key, $column)
    {
        $sql        = 'SELECT * FROM `session` WHERE `id` = ? AND `expire` > ?';
        $db_results = Dba::read($sql, array($key, time()));

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
    public static function username($key)
    {
        return self::_read($key, 'username');
    }

    /**
     * username
     *
     * This returns the agent associated with a session ID, if any
     */
    public static function agent($key)
    {
        return self::_read($key, 'agent');
    }

    /**
     * create
     * This is called when you want to create a new session
     * it takes care of setting the initial cookie, and inserting the first
     * chunk of data, nifty ain't it!
     */
    public static function create($data)
    {
        // Regenerate the session ID to prevent fixation
        switch ($data['type']) {
            case 'api':
                $key = isset($data['apikey'])
                    ? $data['apikey']
                    : md5(uniqid(rand(), true));
                break;
            case 'stream':
                $key = isset($data['sid'])
                    ? $data['sid']
                    : md5(uniqid(rand(), true));
            break;
            case 'mysql':
            default:
                session_regenerate_id();

                // Before refresh we don't have the cookie so we
                // have to use session ID
                $key = session_id();
            break;
        } // end switch on data type

        $username = '';
        if (isset($data['username'])) {
            $username = $data['username'];
        }
        $ip    = $_SERVER['REMOTE_ADDR'] ? inet_pton($_SERVER['REMOTE_ADDR']) : '0';
        $type  = $data['type'];
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }
        $agent = (!empty($data['agent'])) ? $data['agent'] : substr($_SERVER['HTTP_USER_AGENT'], 0, 254);

        if ($type == 'stream') {
            $expire = time() + AmpConfig::get('stream_length');
        } else {
            $expire = time() + AmpConfig::get('session_length');
        }

        $latitude = null;
        if (isset($data['geo_latitude'])) {
            $latitude = $data['geo_latitude'];
        }
        $longitude = null;
        if (isset($data['geo_longitude'])) {
            $longitude = $data['geo_longitude'];
        }
        $geoname = null;
        if (isset($data['geo_name'])) {
            $geoname = $data['geo_name'];
        }

        if (!strlen($value)) {
            $value = ' ';
        }

        /* Insert the row */
        $sql = 'INSERT INTO `session` (`id`,`username`,`ip`,`type`,`agent`,`value`,`expire`,`geo_latitude`,`geo_longitude`, `geo_name`) ' .
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $db_results = Dba::write($sql, array($key, $username, $ip, $type, $agent, $value, $expire, $latitude, $longitude, $geoname));

        if (!$db_results) {
            debug_event('session', 'Session creation failed', '1');

            return false;
        }

        debug_event('session', 'Session created: ' . $key, '5');

        return $key;
    }

    /**
     * check
     *
     * This checks for an existing session. If it's still valid we go ahead
     * and start it and return true.
     */
    public static function check()
    {
        $session_name = AmpConfig::get('session_name');

        // No cookie no go!
        if (!isset($_COOKIE[$session_name])) {
            return false;
        }

        // Set up the cookie params before we start the session.
        // This is vital
        session_write_close();
        session_set_cookie_params(
            AmpConfig::get('cookie_life'),
            AmpConfig::get('cookie_path'),
            AmpConfig::get('cookie_domain'),
            AmpConfig::get('cookie_secure'));

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
     * exists
     * based on the type.
     */
    public static function exists($type, $key)
    {
        // Switch on the type they pass
        switch ($type) {
            case 'api':
                return true;
            case 'stream':
                $sql = 'SELECT * FROM `session` WHERE `id` = ? AND `expire` > ? ' .
                    "AND `type` IN ('api', 'stream')";
                $db_results = Dba::read($sql, array($key, time()));

                if (Dba::num_rows($db_results)) {
                    return true;
                }
            break;
            case 'interface':
                $sql = 'SELECT * FROM `session` WHERE `id` = ? AND `expire` > ?';
                if (AmpConfig::get('use_auth')) {
                    // Build a list of enabled authentication types
                    $types         = AmpConfig::get('auth_methods');
                    $enabled_types = implode("','", $types);
                    $sql .= " AND `type` IN('$enabled_types')";
                }
                $db_results = Dba::read($sql, array($key, time()));

                if (Dba::num_rows($db_results)) {
                    return true;
                }
            break;
            default:
                return false;
        }

        // Default to false
        return false;
    }

    /**
     * extend
     *
     * This takes a SID and extends its expiration.
     */
    public static function extend($sid, $type = null)
    {
        $time = time();
        if ($type == 'stream') {
            $expire = $time + AmpConfig::get('stream_length');
        } else {
            $expire = $time + AmpConfig::get('session_length');
        }

        $sql = 'UPDATE `session` SET `expire` = ? WHERE `id`= ?';
        if ($db_results = Dba::write($sql, array($expire, $sid))) {
            debug_event('session', $sid . ' has been extended to ' . @date('r', $expire) . ' extension length ' . ($expire - $time), 5);
        }

        return $db_results;
    }

    /**
     * update_username
     *
     * This takes a SID and update associated username.
     */
    public static function update_username($sid, $username)
    {
        $sql = 'UPDATE `session` SET `username` = ? WHERE `id`= ?';

        return Dba::write($sql, array($username, $sid));
    }

    /**
     * update_geolocation
     * Update session geolocation.
     * @param string $sid
     * @param float $latitude
     * @param float $longitude
     */
    public static function update_geolocation($sid, $latitude, $longitude, $name)
    {
        if ($sid) {
            $sql = "UPDATE `session` SET `geo_latitude` = ?, `geo_longitude` = ?, `geo_name` = ? WHERE `id` = ?";
            Dba::write($sql, array($latitude, $longitude, $name, $sid));
        } else {
            debug_event('session', 'Missing session id to update geolocation.', 3);
        }
    }

    /**
     * get_geolocation
     * Get session geolocation.
     * @param string $sid
     * @return array
     */
    public static function get_geolocation($sid)
    {
        $location = array();

        if ($sid) {
            $sql        = "SELECT `geo_latitude`, `geo_longitude`, `geo_name` FROM `session` WHERE `id` = ?";
            $db_results = Dba::read($sql, array($sid));
            if ($row = Dba::fetch_assoc($db_results)) {
                $location['latitude']  = $row['geo_latitude'];
                $location['longitude'] = $row['geo_longitude'];
                $location['name']      = $row['geo_name'];
            }
        }

        return $location;
    }

    /**
     * _auto_init
     *
     * This function is called when the object is included, this sets up the
     * session_save_handler
     */
    public static function _auto_init()
    {
        if (!function_exists('session_start')) {
            header("Location:" . AmpConfig::get('web_path') . "/test.php");
            exit;
        }

        session_set_save_handler(
            array('Session', 'open'),
            array('Session', 'close'),
            array('Session', 'read'),
            array('Session', 'write'),
            array('Session', 'destroy'),
            array('Session', 'gc'));

        // Make sure session_write_close is called during the early part of
        // shutdown, to avoid issues with object destruction.
        register_shutdown_function('session_write_close');
    }

    /**
     * create_cookie
     *
     * This is separated into its own function because of some flaws in
     * specific webservers *cough* IIS *cough* which prevent us from setting
     * a cookie at the same time as a header redirect. As such on view of a
     * login a cookie is set with the proper name.
     */
    public static function create_cookie()
    {
        // Set up the cookie prefs before we throw down, this is very important
        $cookie_life   = AmpConfig::get('cookie_life');
        $cookie_path   = AmpConfig::get('cookie_path');
        $cookie_domain = null;
        $cookie_secure = AmpConfig::get('cookie_secure');
        
        session_write_close();
        session_set_cookie_params($cookie_life, $cookie_path, $cookie_domain, $cookie_secure);
        session_name(AmpConfig::get('session_name'));

        /* Start the session */
        self::ungimp_ie();
        session_start();
    }

    /**
     * create_user_cookie
     *
     * This function just creates the user cookie wich contains current username.
     * It must be used for information only.
     *
     * It also creates a cookie to store used language.
     */
    public static function create_user_cookie($username)
    {
        $cookie_life   = AmpConfig::get('cookie_life');
        $session_name  = AmpConfig::get('session_name');
        $cookie_path   = AmpConfig::get('cookie_path');
        $cookie_domain = null;
        $cookie_secure = AmpConfig::get('cookie_secure');

        setcookie($session_name . '_user', $username, $cookie_life, $cookie_path, $cookie_domain, $cookie_secure);
        setcookie($session_name . '_lang', AmpConfig::get('lang'), $cookie_life, $cookie_path, $cookie_domain, $cookie_secure);
    }

    /**
     * create_remember_cookie
     *
     * This function just creates the remember me cookie, nothing special.
     */
    public static function create_remember_cookie($username)
    {
        $remember_length = AmpConfig::get('remember_length');
        $session_name    = AmpConfig::get('session_name');

        $token = self::generateRandomToken(); // generate a token, should be 128 - 256 bit
        self::storeTokenForUser($username, $token, $remember_length);
        $cookie = $username . ':' . $token;
        $mac    = hash_hmac('sha256', $cookie, AmpConfig::get('secret_key'));
        $cookie .= ':' . $mac;

        setcookie($session_name . '_remember', $cookie, time() + $remember_length);
    }

    /**
     * Generate a random token.
     * @return string
     */
    public static function generateRandomToken()
    {
        return md5(uniqid(mt_rand(), true));
    }

    public static function storeTokenForUser($username, $token, $remember_length)
    {
        $sql = "INSERT INTO session_remember (`username`, `token`, `expire`) VALUES (?, ?, ?)";

        return Dba::write($sql, array($username, $token, time() + $remember_length));
    }

    public static function auth_remember()
    {
        $auth  = false;
        $cname = AmpConfig::get('session_name') . '_remember';
        if (isset($_COOKIE[$cname])) {
            list($username, $token, $mac) = explode(':', $_COOKIE[$cname]);
            if ($mac === hash_hmac('sha256', $username . ':' . $token, AmpConfig::get('secret_key'))) {
                $sql        = "SELECT * FROM `session_remember` WHERE `username` = ? AND `token` = ? AND `expire` >= ?";
                $db_results = Dba::read($sql, array($username, $token, time()));
                if (Dba::num_rows($db_results) > 0) {
                    Session::create_cookie();
                    self::create(array(
                        'type' => 'mysql',
                        'username' => $username
                    ));
                    $_SESSION['userdata']['username'] = $username;
                    $auth                             = true;
                }
            }
        }

        return $auth;
    }

    /**
     * ungimp_ie
     *
     * This function sets the cache limiting to public if you are running
     * some flavor of IE and not using HTTPS.
     */
    public static function ungimp_ie()
    {
        // If no https, no ungimpage required
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'on') {
            return true;
        }

        $browser = new Horde_Browser();
        if ($browser->isBrowser('msie')) {
            session_cache_limiter('public');
        }

        return true;
    }
}
