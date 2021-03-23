<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

/**
 *
 * This class handles all of the session related stuff in Ampache
 * it takes over for the vauth libs, and takes some stuff out of other
 * classes where it didn't belong.
 */
class Auth
{
    /**
     * Constructor
     *
     * This should never be called
     */
    private function __construct()
    {
        // Rien a faire
    } // __construct

    /**
     * logout
     *
     * This is called when you want to log out and nuke your session.
     * This is the function used for the Ajax logouts, if no id is passed
     * it tries to find one from the session,
     * @param string $key
     * @param boolean $relogin
     * @return boolean
     */
    public static function logout($key = '', $relogin = true)
    {
        // If no key is passed try to find the session id
        $key = $key ? $key : session_id();

        // Nuke the cookie before all else
        Session::destroy($key);
        if ((!$relogin) && AmpConfig::get('logout_redirect')) {
            $target = AmpConfig::get('logout_redirect');
        } else {
            $target = AmpConfig::get('web_path') . '/login.php';
        }

        // Do a quick check to see if this is an AJAXed logout request
        // if so use the iframe to redirect
        if (defined('AJAX_INCLUDE')) {
            ob_end_clean();
            ob_start();

            xoutput_headers();

            $results            = array();
            $results['rfc3514'] = '<script>reloadRedirect("' . $target . '")</script>';
            echo (string) xoutput_from_array($results);
        } else {
            /* Redirect them to the login page */
            header('Location: ' . $target);
        }

        return false;
    }

    /**
     * login
     *
     * This takes a username and password and then returns the results
     * based on what happens when we try to do the auth.
     * @param string $username
     * @param string $password
     * @param boolean $allow_ui
     * @param string $token
     * @param string $salt
     * @return array
     */
    public static function login($username, $password, $allow_ui = false, $token = null, $salt = null)
    {
        $results = array();
        // check usernames
        if (!in_array($username, User::get_valid_usernames())) {
            return $results;
        }
        // Check for token auth with apikey
        $token_check = self::token_check($username, $token, $salt);
        if (!empty($token_check)) {
            debug_event(self::class, 'Logging in using token auth ' . $token, 5);

            return $token_check;
        }

        // If no token check the regular methods
        foreach (AmpConfig::get('auth_methods') as $method) {
            $function_name = $method . '_auth';

            if (!method_exists('Auth', $function_name)) {
                continue;
            }

            $results = self::$function_name($username, $password);
            if ($results['success'] || ($allow_ui && !empty($results['ui_required']))) {
                break;
            }
        }

        return $results;
    }

    /**
     * login_step2
     *
     * This process authenticate step2 for an auth module
     * @param string $auth_mod
     * @return array
     */
    public static function login_step2($auth_mod)
    {
        $results = null;
        if (in_array($auth_mod, AmpConfig::get('auth_methods'))) {
            $function_name = $auth_mod . '_auth_2';
            if (method_exists('Auth', $function_name)) {
                $results = self::$function_name();
            }
        }

        return $results;
    }

    /**
     * mysql_auth
     *
     * This is the core function of our built-in authentication.
     * @param string $username
     * @param string $password
     * @return array
     */
    private static function mysql_auth($username, $password)
    {
        if (strlen((string) $password) && strlen((string) $username)) {
            $sql        = 'SELECT `password` FROM `user` WHERE `username` = ?';
            $db_results = Dba::read($sql, array($username));

            if ($row = Dba::fetch_assoc($db_results)) {
                // Use SHA2 now... cooking with fire.
                // For backwards compatibility we hash a couple of different
                // variations of the password. Increases collision chances, but
                // doesn't break things.
                // FIXME: Break things in the future.
                $hashed_password   = array();
                $hashed_password[] = hash('sha256', $password);
                $hashed_password[] = hash('sha256',
                    Dba::escape(stripslashes(htmlspecialchars(strip_tags($password)))));

                // Automagically update the password if it's old and busted.
                if ($row['password'] == $hashed_password[1] &&
                    $hashed_password[0] != $hashed_password[1]) {
                    $user = User::get_from_username($username);
                    $user->update_password($password);
                }

                if (in_array($row['password'], $hashed_password)) {
                    return array(
                        'success' => true,
                        'type' => 'mysql',
                        'username' => $username
                    );
                }
            }
            // subsonic password fallback for auth with apikey
            $sub_sql = 'SELECT `apikey` FROM `user` WHERE `username` = ?';
            $results = Dba::read($sub_sql, array($username));
            $row     = Dba::fetch_assoc($results);
            $api_key = $row['apikey'];
            if ($password == $api_key) {
                return array(
                    'success' => true,
                    'type' => 'mysql',
                    'username' => $username
                );
            }
        }

        return array(
            'success' => false,
            'error' => 'MySQL login attempt failed'
        );
    }

    /**
     * pam_auth
     *
     * Check to make sure the pam_auth function is implemented (module is
     * installed), then check the credentials.
     * @param string $username
     * @param string $password
     * @return array
     */
    private static function pam_auth($username, $password)
    {
        $results = array();
        if (!function_exists('pam_auth')) {
            $results['success']    = false;
            $results['error']      = 'The PAM PHP module is not installed';

            return $results;
        }

        $password = scrub_in($password);

        if (pam_auth($username, $password)) {
            $results['success']     = true;
            $results['type']        = 'pam';
            $results['username']    = $username;
        } else {
            $results['success']    = false;
            $results['error']      = 'PAM login attempt failed';
        }

        return $results;
    }

    /**
     * external_auth
     *
     * Calls an external program compatible with mod_authnz_external
     * such as pwauth.
     * @param string $username
     * @param string $password
     * @return array
     */
    private static function external_auth($username, $password)
    {
        $authenticator = AmpConfig::get('external_authenticator');
        if (!$authenticator) {
            return array(
                'success' => false,
                'error' => 'No external authenticator configured'
            );
        }

        // FIXME: should we do input sanitization?
        $proc = proc_open($authenticator,
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            ), $pipes);

        if (is_resource($proc)) {
            fwrite($pipes[0], $username . "\n" . $password . "\n");
            fclose($pipes[0]);
            fclose($pipes[1]);
            if ($stderr = fread($pipes[2], 8192)) {
                debug_event(self::class, "external_auth fread error: " . $stderr, 3);
            }
            fclose($pipes[2]);
        } else {
            return array(
                'success' => false,
                'error' => 'Failed to run external authenticator'
            );
        }

        if (proc_close($proc) == 0) {
            return array(
                'success' => true,
                'type' => 'external',
                'username' => $username
            );
        }

        return array(
            'success' => false,
            'error' => 'The external authenticator did not accept the login'
        );
    }

    /**
     * ldap_auth
     * @param string $username
     * @param string $password
     * @return array
     */
    private static function ldap_auth($username, $password)
    {
        return LDAP::auth($username, $password);
    }

    /**
     * http_auth
     * This auth method relies on HTTP auth from the webserver
     *
     * @param string $username
     * @param string $password
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private static function http_auth($username, $password)
    {
        unset($password);
        $results = array();
        if ((Core::get_server('REMOTE_USER') == $username) ||
            (Core::get_server('HTTP_REMOTE_USER') == $username)) {
            $results['success']     = true;
            $results['type']        = 'http';
            $results['username']    = $username;
            $results['name']        = $username;
            $results['email']       = '';
        } else {
            $results['success'] = false;
            $results['error']   = 'HTTP auth login attempt failed';
        }

        return $results;
    } // http_auth

    /**
     * openid_auth
     * Authenticate user with OpenID
     *
     * @param string $username
     * @param string $password
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private static function openid_auth($username, $password)
    {
        unset($password);
        $results = array();
        // Username contains the openid url. We don't care about password here.
        $website = $username;
        if (strpos($website, 'http://') === 0 || strpos($website, 'https://') === 0) {
            $consumer = Openid::get_consumer();
            if ($consumer) {
                $auth_request = $consumer->begin($website);
                if ($auth_request) {
                    $sreg_request = Auth_OpenID_SRegRequest::build(
                        // Required
                        array('nickname'),
                        // Optional
                        array('fullname', 'email')
                    );
                    if ($sreg_request) {
                        $auth_request->addExtension($sreg_request);
                    }
                    $pape_request = new Auth_OpenID_PAPE_Request(Openid::get_policies());
                    if ($pape_request) {
                        $auth_request->addExtension($pape_request);
                    }

                    // Redirect the user to the OpenID server for authentication.
                    // Store the token for this authentication so we can verify the response.

                    // For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
                    // form to send a POST request to the server.
                    if ($auth_request->shouldSendRedirect()) {
                        $redirect_url = $auth_request->redirectURL(AmpConfig::get('web_path'), Openid::get_return_url());
                        if (Auth_OpenID::isFailure($redirect_url)) {
                            $results['success'] = false;
                            $results['error']   = 'Could not redirect to server: ' . $redirect_url->message;
                        } else {
                            // Send redirect.
                            debug_event(self::class, 'OpenID 1: redirecting to ' . $redirect_url, 5);
                            header("Location: " . $redirect_url);
                        }
                    } else {
                        // Generate form markup and render it.
                        $form_id   = 'openid_message';
                        $form_html = $auth_request->htmlMarkup(AmpConfig::get('web_path'), Openid::get_return_url(), false, array('id' => $form_id));

                        if (Auth_OpenID::isFailure($form_html)) {
                            $results['success'] = false;
                            $results['error']   = 'Could not render authentication form.';
                        } else {
                            debug_event(self::class, 'OpenID 2: javascript redirection code to OpenID form.', 5);
                            // First step is a success, UI interaction required.
                            $results['success']     = false;
                            $results['ui_required'] = $form_html;
                        }
                    }
                } else {
                    debug_event(self::class, $website . ' is not a valid OpenID.', 3);
                    $results['success'] = false;
                    $results['error']   = 'Not a valid OpenID.';
                }
            } else {
                debug_event(self::class, 'Cannot initialize OpenID resources.', 3);
                $results['success'] = false;
                $results['error']   = 'Cannot initialize OpenID resources.';
            }
        } else {
            debug_event(self::class, 'Skipped OpenID authentication: missing scheme in ' . $website . '.', 3);
            $results['success'] = false;
            $results['error']   = 'Missing scheme in OpenID.';
        }

        return $results;
    }

    /**
     * openid_auth_2
     * Authenticate user with OpenID, step 2
     * @return array
     */
    private static function openid_auth_2()
    {
        $results         = array();
        $results['type'] = 'openid';
        $consumer        = Openid::get_consumer();
        if ($consumer) {
            $response = $consumer->complete(Openid::get_return_url());

            if ($response->status == Auth_OpenID_CANCEL) {
                $results['success'] = false;
                $results['error']   = 'OpenID verification cancelled.';
            } else {
                if ($response->status == Auth_OpenID_FAILURE) {
                    $results['success'] = false;
                    $results['error']   = 'OpenID authentication failed: ' . $response->message;
                } else {
                    if ($response->status == Auth_OpenID_SUCCESS) {
                        // Extract the identity URL and Simple Registration data (if it was returned).
                        $sreg_resp    = Auth_OpenID_SRegResponse::fromSuccessResponse($response);
                        $sreg         = $sreg_resp->contents();

                        $results['website'] = $response->getDisplayIdentifier();
                        if (@$sreg['email']) {
                            $results['email'] = $sreg['email'];
                        }

                        if (@$sreg['nickname']) {
                            $results['username'] = $sreg['nickname'];
                        }

                        if (@$sreg['fullname']) {
                            $results['name'] = $sreg['fullname'];
                        }

                        $users = User::get_from_website($results['website']);
                        if (count($users) > 0) {
                            if (count($users) == 1) {
                                $user                = new User($users[0]);
                                $results['success']  = true;
                                $results['username'] = $user->username;
                            } else {
                                // Several users for the same website/openid? Allowed but stupid, try to get a match on username.
                                // Should we make website field unique?
                                foreach ($users as $userid) {
                                    $user = new User($userid);
                                    if ($user->username == $results['username']) {
                                        $results['success']  = true;
                                        $results['username'] = $user->username;
                                    }
                                }
                            }
                        } else {
                            // Don't return success if an user already exists for this username but don't have this openid identity as website
                            $user = User::get_from_username($results['username']);
                            if ($user->id) {
                                $results['success'] = false;
                                $results['error']   = 'No user associated to this OpenID and username already taken.';
                            } else {
                                $results['success'] = true;
                                $results['error']   = 'No user associated to this OpenID.';
                            }
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * token_check
     *
     * Check if the supplied token and salt match this user.
     * @param string $username
     * @param string $token
     * @param string $salt
     * @return array
     */
    public static function token_check($username, $token, $salt)
    {
        // subsonic token auth with apikey
        if (strlen((string) $token) && strlen((string) $salt) && strlen((string) $username)) {
            $sql        = 'SELECT `apikey`, `username` FROM `user` WHERE `username` = ?';
            $db_results = Dba::read($sql, array($username));
            $row        = Dba::fetch_assoc($db_results);
            $hash_token = hash('md5', ($row['apikey'] . $salt));
            if ($token == $hash_token && $row['username'] == $username && isset($row['apikey'])) {
                return array(
                    'success' => true,
                    'type' => 'api',
                    'username' => $username
                );
            }
        }

        return array();
    }
} // end auth.class
