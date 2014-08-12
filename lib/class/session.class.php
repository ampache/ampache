<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

        // Check to see if remember me cookie is set, if so use remember
        // length, otherwise use the session length
        $expire = isset($_COOKIE[AmpConfig::get('session_name') . '_remember'])
            ? time() + AmpConfig::get('remember_length')
            : time() + AmpConfig::get('session_length');

        $sql = 'UPDATE `session` SET `value` = ?, `expire` = ? WHERE `id` = ?';
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
        if (!strlen($key)) { return false; }

        // Remove anything and EVERYTHING
        $sql = 'DELETE FROM `session` WHERE `id` = ?';
        Dba::write($sql, array($key));

        debug_event('SESSION', 'Deleting Session with key:' . $key, 6);

        // Destroy our cookie!
        setcookie(AmpConfig::get('session_name'), '', time() - 86400);

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
        $sql = 'SELECT * FROM `session` WHERE `id` = ? AND `expire` > ?';
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
        $ip = $_SERVER['REMOTE_ADDR'] ? inet_pton($_SERVER['REMOTE_ADDR']) : '0';
        $type = $data['type'];
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

        if (!strlen($value)) { $value = ' '; }

        /* Insert the row */
        $sql = 'INSERT INTO `session` (`id`,`username`,`ip`,`type`,`agent`,`value`,`expire`) ' .
            'VALUES (?, ?, ?, ?, ?, ?, ?)';
        $db_results = Dba::write($sql, array($key, $username, $ip, $type, $agent, $value, $expire));

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
        if (!isset($_COOKIE[$session_name])) { return false; }

        // Check for a remember me
        if (isset($_COOKIE[$session_name . '_remember'])) {
            self::create_remember_cookie();
        }

        // Set up the cookie params before we start the session.
        // This is vital
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
                    $types = AmpConfig::get('auth_methods');
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
        $expire = isset($_COOKIE[AmpConfig::get('session_name') . '_remember'])
            ? $time + AmpConfig::get('remember_length')
            : $time + AmpConfig::get('session_length');

        if ($type == 'stream') {
            $expire = $time + AmpConfig::get('stream_length');
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
        $cookie_life = AmpConfig::get('cookie_life');
        $cookie_path = AmpConfig::get('cookie_path');
        $cookie_domain = null;
        $cookie_secure = AmpConfig::get('cookie_secure');

        session_set_cookie_params($cookie_life, $cookie_path, $cookie_domain, $cookie_secure);

        session_name(AmpConfig::get('session_name'));

        /* Start the session */
        self::ungimp_ie();
        session_start();
    }

    /**
     * create_remember_cookie
     *
     * This function just creates the remember me cookie, nothing special.
     */
    public static function create_remember_cookie()
    {
        $remember_length = AmpConfig::get('remember_length');
        $session_name = AmpConfig::get('session_name');

        AmpConfig::set('cookie_life', $remember_length, true);
        setcookie($session_name . '_remember', "Rappelez-vous, rappelez-vous le 27 mars", time() + $remember_length, '/');
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
