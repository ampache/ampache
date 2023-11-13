<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=0);

namespace Ampache\Module\System;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Query;
use Ampache\Repository\Model\User;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Horde_Browser;
use Ampache\Config\AmpConfig;
use Ampache\Repository\UserRepositoryInterface;
use PDOStatement;
use Ampache\Repository\Model\Song_Preview;
use Ampache\Repository\Model\Tmp_Playlist;

/**
 * This class handles all of the session related stuff in Ampache
 */
final class Session implements SessionInterface
{
    private ConfigContainerInterface $configContainer;

    private AuthenticationManagerInterface $authenticationManager;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        AuthenticationManagerInterface $authenticationManager,
        UserRepositoryInterface $userRepository
    ) {
        $this->configContainer       = $configContainer;
        $this->authenticationManager = $authenticationManager;
        $this->userRepository        = $userRepository;
    }

    public function auth(): bool
    {
        $useAuth          = $this->configContainer->isAuthenticationEnabled();
        $sessionName      = $this->configContainer->get('session_name');
        $isDemoMode       = $this->configContainer->get('demo_mode');
        $defaultAuthLevel = $this->configContainer->get('default_auth_level');

        // If we want a session
        if (!defined('NO_SESSION') && $useAuth) {
            $sessionData = $_COOKIE[$sessionName] ?? '';
            // Verify their session
            if (!self::exists('interface', $sessionData)) {
                if (!self::auth_remember()) {
                    $this->authenticationManager->logout($sessionData);

                    return false;
                }
            }

            // This actually is starting the session
            self::check();

            // Create the new user
            $GLOBALS['user'] = (array_key_exists('userdata', $_SESSION) && array_key_exists('username', $_SESSION['userdata']))
                ? User::get_from_username($_SESSION['userdata']['username'])
                : '';

            // If the user ID doesn't exist deny them
            $user_id = (!empty(Core::get_global('user'))) ? Core::get_global('user')->id : false;
            if (!$user_id && !$isDemoMode) {
                $this->authenticationManager->logout(session_id());

                return false;
            }

            $this->userRepository->updateLastSeen((int) Core::get_global('user')->id);
        } elseif (!$useAuth) {
            $auth                 = array();
            $auth['success']      = 1;
            $auth['username']     = '-1';
            $auth['fullname']     = "Ampache User";
            $auth['id']           = -1;
            $auth['offset_limit'] = 50;
            $auth['access']       = $defaultAuthLevel ? User::access_name_to_level($defaultAuthLevel) : '5';
            if (!array_key_exists($sessionName, $_COOKIE) || (!self::exists('interface', $_COOKIE[$sessionName]))) {
                self::create_cookie();
                self::create($auth);
                self::check();
                $GLOBALS['user']           = new User('-1');
                $GLOBALS['user']->username = $auth['username'];
                $GLOBALS['user']->fullname = $auth['fullname'];
                $GLOBALS['user']->access   = (int) ($auth['access']);
            } else {
                self::check();
                if (array_key_exists('userdata', $_SESSION) && array_key_exists('username', $_SESSION['userdata'])) {
                    self::createGlobalUser(User::get_from_username($_SESSION['userdata']['username']));
                } else {
                    $GLOBALS['user']           = new User('-1');
                    $GLOBALS['user']->id       = -1;
                    $GLOBALS['user']->username = $auth['username'];
                    $GLOBALS['user']->fullname = $auth['fullname'];
                    $GLOBALS['user']->access   = (int)$auth['access'];
                }
                $user_id = (!empty(Core::get_global('user'))) ? Core::get_global('user')->id : false;
                if (!$user_id && !$isDemoMode) {
                    $this->authenticationManager->logout(session_id());

                    return false;
                }
                $this->userRepository->updateLastSeen((int) Core::get_global('user')->id);
            }
        } else {
            // If Auth, but no session is set
            if (array_key_exists('sid', $_REQUEST)) {
                session_name($sessionName);
                session_id(scrub_in((string) $_REQUEST['sid']));
                session_start();
                self::createGlobalUser(new User($_SESSION['userdata']['uid']));
            } else {
                $GLOBALS['user'] = '';
            }
        } // If NO_SESSION passed

        return true;
    }

    /**
     * write
     *
     * This saves the session information into the database.
     * @param $key
     * @param $value
     * @return bool
     */
    public static function write($key, $value)
    {
        if (defined('NO_SESSION_UPDATE')) {
            return true;
        }

        $expire = time() + AmpConfig::get('session_length', 3600);
        $sql    = 'UPDATE `session` SET `value` = ?, `expire` = ? WHERE `id` = ?';
        Dba::write($sql, array($value, $expire, $key));

        debug_event(self::class, 'Writing to ' . $key . ' with expiration ' . $expire, 5);

        return true;
    }

    /**
     * destroy
     *
     * This removes the specified session from the database.
     * @param string $key
     * @return bool
     */
    public static function destroy($key)
    {
        if (!strlen((string)$key)) {
            return false;
        }

        $sql = 'DELETE FROM `session` WHERE `id` = ?';
        Dba::write($sql, array($key));

        debug_event(self::class, 'Deleting Session with key:' . $key, 6);

        $session_name   = AmpConfig::get('session_name');
        $cookie_options = [
            'expires' => -1,
            'path' => (string)AmpConfig::get('cookie_path'),
            'domain' => (string)AmpConfig::get('cookie_domain'),
            'secure' => make_bool(AmpConfig::get('cookie_secure')),
            'samesite' => 'Strict'
        ];

        // Destroy our cookie!
        setcookie($session_name, '', $cookie_options);
        setcookie($session_name, '', -1);
        setcookie($session_name . '_user', '', $cookie_options);
        setcookie($session_name . '_lang', '', $cookie_options);

        return true;
    }

    /**
     * garbage_collection
     *
     * This function is randomly called and it cleans up the expired sessions
     */
    public static function garbage_collection()
    {
        $sql = 'DELETE FROM `session` WHERE `expire` < ?';
        Dba::write($sql, array(time()));

        $sql = 'DELETE FROM `session_remember` WHERE `expire` < ?;';
        Dba::write($sql, array(time()));

        // Also clean up things that use sessions as keys
        Query::garbage_collection();
        Tmp_Playlist::garbage_collection();
        Stream_Playlist::garbage_collection();
        Song_Preview::garbage_collection();
    }

    /**
     * read
     *
     * This takes a key and returns the data from the database.
     * @param $key
     * @return string
     */
    public static function read($key)
    {
        return self::_read($key, 'value');
    }

    /**
     * _read
     *
     * This returns the specified column from the session row.
     * @param string $key
     * @param string $column
     * @return string
     */
    private static function _read($key, $column)
    {
        $sql        = 'SELECT * FROM `session` WHERE `id` = ? AND `expire` > ?';
        $db_results = Dba::read($sql, array($key, time()));

        if ($results = Dba::fetch_assoc($db_results)) {
            //debug_event(self::class, 'Read session from key ' . $key . ' ' . $results[$column], 3);

            return $results[$column];
        }

        debug_event(self::class, 'Unable to read ' . $column . ' from key ' . $key . ' no data found', 3);

        return '';
    }

    /**
     * username
     *
     * This returns the username associated with a session ID, if any
     * @param $key
     * @return string
     */
    public static function username($key)
    {
        return self::_read($key, 'username');
    }

    /**
     * agent
     *
     * This returns the agent associated with a session ID, if any
     * @param string $key
     * @return string
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
     * @param array $data
     * @return string
     */
    public static function create($data)
    {
        $type = $data['type'] ?? '';
        // Regenerate the session ID to prevent fixation
        switch ($type) {
            case 'header':
                $type = 'api';
                $key  = $data['apikey'];
                break;
            case 'api':
                $key = (isset($data['apikey'])) ? md5(((string) $data['apikey'] . md5(uniqid((string) rand(), true)))) : md5(uniqid((string) rand(), true));
                break;
            case 'stream':
                $key = (isset($data['sid'])) ? $data['sid'] : md5(uniqid((string)rand(), true));
                break;
            case 'mysql':
            default:
                session_regenerate_id();

                // Before refresh we don't have the cookie so we have to use session ID
                $key = session_id();
                break;
        } // end switch on data type

        $username = '';
        if (isset($data['username'])) {
            $username = $data['username'];
        }
        $s_ip  = isset($_SERVER['REMOTE_ADDR']) ? filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) : '0';
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }
        $agent = (!empty($data['agent'])) ? $data['agent'] : substr(Core::get_server('HTTP_USER_AGENT'), 0, 254);

        $expire = time() + AmpConfig::get('session_length', 3600);
        if ($type == 'stream') {
            $expire = time() + AmpConfig::get('stream_length');
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

        if (!strlen((string)$value)) {
            $value = ' ';
        }

        /* Insert the row */
        $sql        = 'INSERT INTO `session` (`id`, `username`, `ip`, `type`, `agent`, `value`, `expire`, `geo_latitude`, `geo_longitude`, `geo_name`) ' . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $db_results = Dba::write($sql, array($key, $username, $s_ip, $type, $agent, $value, $expire, $latitude, $longitude, $geoname));

        if (!$db_results) {
            debug_event(self::class, 'Session creation failed', 1);

            return '';
        }

        debug_event(self::class, 'Session created: ' . $key, 5);

        return $key;
    }

    /**
     * check
     *
     * This checks for an existing session. If it's still valid we go ahead
     * and start it and return true.
     * @return bool
     */
    public static function check()
    {
        $session_name = AmpConfig::get('session_name');

        // No cookie no go!
        if (!isset($_COOKIE[$session_name])) {
            if (!self::auth_remember()) {
                debug_event(self::class, 'Existing session NOT found', 5);

                return false;
            }
            debug_event(self::class, 'auth_remember session found', 5);
        }

        // Can't do cookie is the session is already started
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        $cookie_params = [
            'lifetime' => (int)AmpConfig::get('cookie_life'),
            'path' => (string)AmpConfig::get('cookie_path'),
            'domain' => (string)AmpConfig::get('cookie_domain'),
            'secure' => make_bool(AmpConfig::get('cookie_secure')),
            'samesite' => 'Lax'
        ];

        // Set up the cookie params before we start the session.
        // This is vital
        session_set_cookie_params($cookie_params);
        session_write_close();

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
     * @param string $type
     * @param string $key
     * @return bool
     */
    public static function exists($type, $key)
    {
        // didn't pass an auth key so don't let them in!
        if (!$key) {
            return false;
        }
        // Switch on the type they pass
        switch ($type) {
            case 'api':
            case 'stream':
                $sql        = 'SELECT * FROM `session` WHERE `id` = ? AND `expire` > ? ' . "AND `type` in ('api', 'stream')"; // TODO why are these together?
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
                    $enabled_types = implode("', '", $types);
                    $sql .= " AND `type` IN('$enabled_types')";
                }
                $db_results = Dba::read($sql, array($key, time()));

                if (Dba::num_rows($db_results)) {
                    $results = Dba::fetch_assoc($db_results);
                    if ($results) {
                        self::createGlobalUser(User::get_from_username($results['username']));
                    }

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
     * @param string $sid
     * @param string $type
     * @return PDOStatement|bool
     */
    public static function extend($sid, $type = null)
    {
        $time = time();
        if ($type == 'stream') {
            $expire = $time + AmpConfig::get('stream_length');
        } else {
            $expire = $time + AmpConfig::get('session_length', 3600);
        }

        $sql = 'UPDATE `session` SET `expire` = ? WHERE `id`= ?';
        if ($db_results = Dba::write($sql, array($expire, $sid))) {
            debug_event(self::class, $sid . ' has been extended to ' . @date('r', $expire) . ' extension length ' . ($expire - $time), 5);
            $results = Dba::fetch_assoc($db_results);
            if ($results) {
                self::createGlobalUser(User::get_from_username($results['username']));
            }
        }

        return $db_results;
    }

    /**
     * get
     *
     * This checks for an existing session cookie and returns the value.
     * @return string
     */
    public static function get()
    {
        $session_name = AmpConfig::get('session_name');

        if (array_key_exists($session_name, $_COOKIE)) {
            return $_COOKIE[$session_name];
        }

        return '';
    }

    /**
     * update_username
     *
     * This takes a SID and update associated username.
     * @param string $sid
     * @param string $username
     * @return PDOStatement|bool
     */
    public static function update_username($sid, $username)
    {
        $sql = 'UPDATE `session` SET `username` = ? WHERE `id`= ?';

        return Dba::write($sql, array($username, $sid));
    }

    /**
     * update_agent
     *
     * This takes a SID and update associated agent.
     * @param string $sid
     * @param string $agent
     * @return PDOStatement|bool
     */
    public static function update_agent($sid, $agent)
    {
        $sql = 'UPDATE `session` SET `agent` = ? WHERE `id`= ?';

        return Dba::write($sql, array($agent, $sid));
    }

    /**
     * update_geolocation
     * Update session geolocation.
     * @param string $sid
     * @param float $latitude
     * @param float $longitude
     * @param string $name
     */
    public static function update_geolocation($sid, $latitude, $longitude, $name)
    {
        if ($sid) {
            $sql = "UPDATE `session` SET `geo_latitude` = ?, `geo_longitude` = ?, `geo_name` = ? WHERE `id` = ?";
            Dba::write($sql, array($latitude, $longitude, $name, $sid));
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
     * get_api_version
     * Get session geolocation.
     * @param string $sid
     * @return int
     */
    public static function get_api_version($sid)
    {
        $api_version = 6; // AMPACHE_VERSION

        if ($sid) {
            $sql        = "SELECT `value` FROM `session` WHERE `type` = 'api' AND `id` = ?;";
            $db_results = Dba::read($sql, array($sid));
            $row        = Dba::fetch_assoc($db_results);
            if (!empty($row)) {
                $api_version =  (int)$row['value'];
            }
        }

        return $api_version;
    }

    public function setup(): void
    {
        // enforce strict cookies. you don't need these elsewhere
        ini_set('session.cookie_samesite', 'Lax');

        session_set_save_handler(
            static function (): bool {
                return true;
            },
            static function (): bool {
                return true;
            },
            static function ($key) {
                return self::_read($key, 'value');
            },
            static function ($key, $value): bool {
                return self::write($key, $value);
            },
            static function ($key): bool {
                return self::destroy($key);
            },
            static function (): void {
                self::garbage_collection();
            }
        );

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
        $cookie_params = [
            'lifetime' => (int)AmpConfig::get('cookie_life'),
            'path' => (string)AmpConfig::get('cookie_path'),
            'domain' => (string)AmpConfig::get('cookie_domain'),
            'secure' => make_bool(AmpConfig::get('cookie_secure')),
            'samesite' => 'Lax'
        ];
        if (isset($_SESSION)) {
            $cookie_options = [
                'expires' => (int)AmpConfig::get('cookie_life'),
                'path' => (string)AmpConfig::get('cookie_path'),
                'domain' => (string)AmpConfig::get('cookie_domain'),
                'secure' => make_bool(AmpConfig::get('cookie_secure')),
                'samesite' => 'Lax'
            ];
            setcookie(session_name(), session_id(), $cookie_options);
        } else {
            session_set_cookie_params($cookie_params);
        }
        session_write_close();
        session_name(AmpConfig::get('session_name'));
        session_set_cookie_params($cookie_params);

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
     * @param string $username
     */
    public static function create_user_cookie($username)
    {
        $session_name   = AmpConfig::get('session_name');
        $cookie_options = [
            'expires' => (int)AmpConfig::get('cookie_life'),
            'path' => (string)AmpConfig::get('cookie_path'),
            'domain' => (string)AmpConfig::get('cookie_domain'),
            'secure' => make_bool(AmpConfig::get('cookie_secure')),
            'samesite' => 'Strict'
        ];

        setcookie($session_name . '_user', $username, $cookie_options);
        setcookie($session_name . '_lang', AmpConfig::get('lang'), $cookie_options);
    }

    /**
     * create_remember_cookie
     *
     * This function just creates the remember me cookie, nothing special.
     * @param string $username
     */
    public static function create_remember_cookie($username)
    {
        $session_name    = AmpConfig::get('session_name');
        $remember_length = (int)(time() + AmpConfig::get('remember_length', 604800));
        $cookie_options  = [
            'expires' => $remember_length,
            'path' => (string)AmpConfig::get('cookie_path'),
            'domain' => (string)AmpConfig::get('cookie_domain'),
            'secure' => make_bool(AmpConfig::get('cookie_secure')),
            'samesite' => 'Strict'
        ];

        $token = self::generateRandomToken(); // generate a token, should be 128 - 256 bit
        self::storeTokenForUser($username, $token, $remember_length);
        $cookie = $username . ':' . $token;
        $mac    = hash_hmac('sha256', $cookie, AmpConfig::get('secret_key'));
        $cookie .= ':' . $mac;

        setcookie($session_name . '_remember', $cookie, $cookie_options);
    }

    /**
     * generateRandomToken
     * Generate a random token.
     * @return string
     */
    public static function generateRandomToken()
    {
        return md5(uniqid((string)bin2hex(random_bytes(20)), true));
    }

    /**
     * createGlobalUser
     * Set up the global user
     */
    public static function createGlobalUser(?User $user)
    {
        if (empty(Core::get_global('user'))) {
            if ($user instanceof User && $user->id > 0) {
                $GLOBALS['user'] = $user;
            } elseif (isset($_SESSION) && array_key_exists('userdata', $_SESSION) && array_key_exists('username', $_SESSION['userdata'])) {
                $GLOBALS['user'] =  User::get_from_username($_SESSION['userdata']['username']);
            }
        }
    }

    /**
     * storeTokenForUser
     * @param string $username
     * @param string $token
     * @param int $remember_length
     * @return PDOStatement|bool
     */
    public static function storeTokenForUser($username, $token, $remember_length)
    {
        $sql = "INSERT INTO session_remember (`username`, `token`, `expire`) VALUES (?, ?, ?)";

        return Dba::write($sql, array($username, $token, $remember_length));
    }

    /**
     * auth_remember
     * @return bool
     */
    public static function auth_remember()
    {
        $auth  = false;
        $cname = AmpConfig::get('session_name') . '_remember';
        if (isset($_COOKIE[$cname])) {
            [$username, $token, $mac] = explode(':', $_COOKIE[$cname]);
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
            // make sure the global is set too
            self::createGlobalUser(User::get_from_username($_SESSION['userdata']['username']));
            // make sure the prefs are set too
            Preference::init();
        }

        return $auth;
    }

    /**
     * ungimp_ie
     *
     * This function sets the cache limiting to public if you are running
     * some flavor of IE and not using HTTPS.
     *
     * @todo check if we still need to do this today
     */
    public static function ungimp_ie()
    {
        // If no https, no ungimpage required
        if (isset($_SERVER['HTTPS']) && Core::get_server('HTTPS') != 'on') {
            return true;
        }

        $browser = new Horde_Browser();
        if ($browser->isBrowser('msie')) {
            session_cache_limiter('public');
        }

        return true;
    }
}
