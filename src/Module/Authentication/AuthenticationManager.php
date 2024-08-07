<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Authentication;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Authentication\Authenticator\AuthenticatorInterface;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;

final class AuthenticationManager implements AuthenticationManagerInterface
{
    /** @var AuthenticatorInterface[] $authenticatorList */
    private array $authenticatorList;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer,
        array $authenticatorList
    ) {
        $this->configContainer   = $configContainer;
        $this->authenticatorList = $authenticatorList;
    }

    public function login(
        string $username,
        string $password,
        bool $allow_ui = false
    ): array {
        $result = [];

        foreach ($this->configContainer->get('auth_methods') as $method) {
            $authenticator = $this->authenticatorList[$method] ?? null;

            if ($authenticator === null) {
                continue;
            }

            $result = $authenticator->auth($username, $password);
            if ($result['success'] || ($allow_ui && !empty($result['ui_required']))) {
                break;
            }
        }

        return $result;
    }

    public function postAuth(string $method): ?array
    {
        $result = [];

        if (in_array($method, $this->configContainer->get('auth_methods'))) {
            $authenticator = $this->authenticatorList[$method] ?? null;

            if ($authenticator !== null) {
                $result = $authenticator->postAuth();
            }
        }

        return $result;
    }

    public function tokenLogin(
        string $username,
        string $token,
        string $salt
    ): array {
        // subsonic token auth with apikey
        if (strlen((string)$token) && strlen((string)$salt) && strlen((string)$username)) {
            $sql        = 'SELECT `apikey`, `username` FROM `user` WHERE `username` = ?';
            $db_results = Dba::read($sql, [$username]);
            $row        = Dba::fetch_assoc($db_results);
            if (isset($row['apikey'])) {
                $hash_token = hash('md5', ($row['apikey'] . $salt));
                if ($token === $hash_token && $row['username'] === $username) {
                    return [
                        'success' => true,
                        'type' => 'api',
                        'username' => $username
                    ];
                }
            }
        }

        return [];
    }

    /**
     * This is called when you want to log out and nuke your session.
     * This is the function used for the Ajax logouts, if no id is passed
     * it tries to find one from the session,
     */
    public function logout(string $key = '', bool $relogin = true): void
    {
        // If no key is passed try to find the session id
        $key = empty($key) ? session_id() : $key;

        // Nuke the cookie before all else
        Session::destroy((string)$key);
        if ((!$relogin) && $this->configContainer->get('logout_redirect')) {
            $target = $this->configContainer->get('logout_redirect');
        } else {
            $target = $this->configContainer->get('web_path') . '/client/login.php';
        }

        // Do a quick check to see if this is an AJAXed logout request
        // if so use the iframe to redirect
        if (defined('AJAX_INCLUDE')) {
            ob_end_clean();
            ob_start();

            xoutput_headers();

            $results             = [];
            $results['reloader'] = '<script>reloadRedirect("' . $target . '")</script>';
            echo (string)xoutput_from_array($results);
        } else {
            /* Redirect them to the login page */
            header('Location: ' . $target);
        }
    }
}
