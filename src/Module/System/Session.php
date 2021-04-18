<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Util\CookieSetterInterface;
use Ampache\Module\Util\Horde_Browser;
use Ampache\Repository\Model\User;
use Ampache\Repository\SessionRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use PDOStatement;

/**
 * This class handles all of the session related stuff in Ampache
 */
final class Session implements SessionInterface
{
    private ConfigContainerInterface $configContainer;

    private AuthenticationManagerInterface $authenticationManager;

    private UserRepositoryInterface $userRepository;

    private SessionRepositoryInterface $sessionRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        AuthenticationManagerInterface $authenticationManager,
        UserRepositoryInterface $userRepository,
        SessionRepositoryInterface $sessionRepository
    ) {
        $this->configContainer       = $configContainer;
        $this->authenticationManager = $authenticationManager;
        $this->userRepository        = $userRepository;
        $this->sessionRepository     = $sessionRepository;
    }

    public function auth(): bool
    {
        $useAuth          = $this->configContainer->isAuthenticationEnabled();
        $sessionName      = $this->configContainer->get('session_name');
        $isDemoMode       = $this->configContainer->get('demo_mode');
        $defaultAuthLevel = $this->configContainer->get('default_auth_level');

        // If we want a session
        if (!defined('NO_SESSION') && $useAuth) {
            // Verify their session
            if (!static::exists('interface', $_COOKIE[$sessionName])) {
                if (!static::auth_remember()) {
                    $this->authenticationManager->logout($_COOKIE[$sessionName] ?? '');

                    return false;
                }
            }

            // This actually is starting the session
            static::check();

            // Create the new user
            $GLOBALS['user'] = User::get_from_username($_SESSION['userdata']['username']);

            // If the user ID doesn't exist deny them
            if (!Core::get_global('user')->id && !$isDemoMode) {
                $this->authenticationManager->logout(session_id());

                return false;
            }

            $this->userRepository->updateLastSeen((int) Core::get_global('user')->id);
        } elseif (!$useAuth) {
            $auth['success']      = 1;
            $auth['username']     = '-1';
            $auth['fullname']     = "Ampache User";
            $auth['id']           = -1;
            $auth['offset_limit'] = 50;
            $auth['access']       = $defaultAuthLevel ? User::access_name_to_level($defaultAuthLevel) : '100';
            if (!static::exists('interface', $_COOKIE[$sessionName])) {
                static::create_cookie();
                static::create($auth);
                static::check();
                $GLOBALS['user']           = new User('-1');
                $GLOBALS['user']->username = $auth['username'];
                $GLOBALS['user']->fullname = $auth['fullname'];
                $GLOBALS['user']->access   = (int) ($auth['access']);
            } else {
                static::check();
                if ($_SESSION['userdata']['username']) {
                    $GLOBALS['user'] = User::get_from_username($_SESSION['userdata']['username']);
                } else {
                    $GLOBALS['user']           = new User('-1');
                    $GLOBALS['user']->id       = -1;
                    $GLOBALS['user']->username = $auth['username'];
                    $GLOBALS['user']->fullname = $auth['fullname'];
                    $GLOBALS['user']->access   = (int) ($auth['access']);
                }
                if (!Core::get_global('user')->id && !$isDemoMode) {
                    $this->authenticationManager->logout(session_id());

                    return false;
                }
                $this->userRepository->updateLastSeen((int) Core::get_global('user')->id);
            }
        } else {
            // If Auth, but no session is set
            if (isset($_REQUEST['sid'])) {
                session_name($sessionName);
                session_id(scrub_in((string) $_REQUEST['sid']));
                session_start();
                $GLOBALS['user'] = new User($_SESSION['userdata']['uid']);
            } else {
                $GLOBALS['user'] = new User();
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
     * @return boolean
     */
    public static function write($key, $value)
    {
        if (defined('NO_SESSION_UPDATE')) {
            return true;
        }

        $expire = time() + AmpConfig::get('session_length');
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
     * @return boolean
     */
    public static function destroy($key)
    {
        if (!strlen((string)$key)) {
            return false;
        }

        $sql = 'DELETE FROM `session` WHERE `id` = ?';
        Dba::write($sql, array($key));

        debug_event(self::class, 'Deleting Session with key:' . $key, 6);

        $session_name  = AmpConfig::get('session_name');

        $options = [
            'expires' => -1,
            'path' => AmpConfig::get('cookie_path'),
            'domain' => '',
            'secure' => make_bool(AmpConfig::get('cookie_secure'))
        ];

        $cookieSetter = static::getCookieSetter();

        // Destroy our cookie!
        $cookieSetter->set($session_name, '', $options);
        $cookieSetter->set(sprintf('%s_user', $session_name), '', $options);
        $cookieSetter->set(sprintf('%s_lang', $session_name), '', $options);

        return true;
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
            // debug_event(self::class, 'Read session from key ' . $key . ' ' . $results[$column], 3);
            return $results[$column];
        }

        debug_event(self::class, 'Unable to read session from key ' . $key . ' no data found', 3);

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
        // Regenerate the session ID to prevent fixation
        switch ($data['type']) {
            case 'api':
                $key = (isset($data['apikey'])) ? md5(((string) $data['apikey'] . md5(uniqid((string) rand(), true)))) : md5(uniqid((string) rand(), true));
                break;
            case 'stream':
                $key = (isset($data['sid'])) ? $data['sid'] : md5(uniqid((string)rand(), true));
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
        $s_ip = filter_has_var(INPUT_SERVER, 'REMOTE_ADDR') ? filter_var(Core::get_server('REMOTE_ADDR'),
            FILTER_VALIDATE_IP) : '0';
        $type  = $data['type'];
        $value = '';
        if (isset($data['value'])) {
            $value = $data['value'];
        }
        $agent = (!empty($data['agent'])) ? $data['agent'] : substr(Core::get_server('HTTP_USER_AGENT'), 0, 254);

        $expire = time() + AmpConfig::get('session_length');
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
        $db_results = Dba::write($sql,
            array($key, $username, $s_ip, $type, $agent, $value, $expire, $latitude, $longitude, $geoname));

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
     * @return boolean
     */
    public static function check()
    {
        $session_name = AmpConfig::get('session_name');

        // No cookie no go!
        if (!filter_has_var(INPUT_COOKIE, $session_name)) {
            debug_event(self::class, 'Existing session NOT found', 5);

            return false;
        }

        // Set up the cookie params before we start the session.
        // This is vital
        session_set_cookie_params((int)AmpConfig::get('cookie_life'), (string)AmpConfig::get('cookie_path'),
            (string)AmpConfig::get('cookie_domain'), make_bool(AmpConfig::get('cookie_secure')));
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
     * @return boolean
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
                $sql        = 'SELECT * FROM `session` WHERE `id` = ? AND `expire` > ? ' . "AND `type` in ('stream', 'api')";
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
     * @return PDOStatement|boolean
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
            debug_event(self::class, $sid . ' has been extended to ' . @date('r', $expire) . ' extension length ' . ($expire - $time), 5);
        }

        return $db_results;
    }

    /**
     * update_username
     *
     * This takes a SID and update associated username.
     * @param string $sid
     * @param string $username
     * @return PDOStatement|boolean
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

    public function setup(): void
    {
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
            function (): void {
                $this->sessionRepository->collectGarbage();
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
        // Set up the cookie prefs before we throw down, this is very important
        $cookie_life   = (int)AmpConfig::get('cookie_life');
        $cookie_path   = (string)AmpConfig::get('cookie_path');
        $cookie_secure = make_bool(AmpConfig::get('cookie_secure'));

        if (isset($_SESSION)) {
            static::getCookieSetter()->set(
                session_name(),
                session_id(),
                [
                    'expires' => $cookie_life,
                    'path' => $cookie_path,
                    'domain' => '',
                    'secure' => $cookie_secure
                ]
            );
        } else {
            session_set_cookie_params($cookie_life, $cookie_path, '', $cookie_secure);
        }
        session_write_close();
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
     * @param string $username
     */
    public static function create_user_cookie($username)
    {
        $session_name  = AmpConfig::get('session_name');
        $options       = [
            'expires' => AmpConfig::get('cookie_life'),
            'path' => AmpConfig::get('cookie_path'),
            'domain' => '',
            'secure' => make_bool(AmpConfig::get('cookie_secure')),
        ];

        $cookieSetter = static::getCookieSetter();
        $cookieSetter->set(
            sprintf('%s_user', $session_name),
            $username,
            $options
        );
        $cookieSetter->set(
            sprintf('%s_lang', $session_name),
            AmpConfig::get('lang'),
            $options
        );
    }

    /**
     * create_remember_cookie
     *
     * This function just creates the remember me cookie, nothing special.
     * @param string $username
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

        static::getCookieSetter()->set(
            sprintf('%s_remember', $session_name),
            $cookie,
            [
                'expires' => time() + $remember_length,
                'path' => AmpConfig::get('cookie_path'),
                'domain' => '',
                'secure' => make_bool(AmpConfig::get('cookie_secure')),
            ]
        );
    }

    /**
     * generateRandomToken
     * Generate a random token.
     * @return string
     */
    public static function generateRandomToken()
    {
        return md5(uniqid((string)mt_rand(), true));
    }

    /**
     * storeTokenForUser
     * @param string $username
     * @param string $token
     * @param integer $remember_length
     * @return PDOStatement|boolean
     */
    public static function storeTokenForUser($username, $token, $remember_length)
    {
        $sql = "INSERT INTO session_remember (`username`, `token`, `expire`) VALUES (?, ?, ?)";

        return Dba::write($sql, array($username, $token, time() + $remember_length));
    }

    /**
     * auth_remember
     * @return boolean
     */
    public static function auth_remember()
    {
        $auth  = false;
        $cname = AmpConfig::get('session_name') . '_remember';
        if (filter_has_var(INPUT_COOKIE, $cname)) {
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
        if (filter_has_var(INPUT_SERVER, 'HTTPS') && filter_input(INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES) != 'on') {
            return true;
        }

        $browser = new Horde_Browser();
        if ($browser->isBrowser('msie')) {
            session_cache_limiter('public');
        }

        return true;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getCookieSetter(): CookieSetterInterface
    {
        global $dic;

        return $dic->get(CookieSetterInterface::class);
    }
}
