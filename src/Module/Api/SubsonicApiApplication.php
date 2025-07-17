<?php

declare(strict_types=0);

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

namespace Ampache\Module\Api;

use Ampache\Module\Api\Authentication\Gatekeeper;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Log\LoggerInterface;

final class SubsonicApiApplication implements ApiApplicationInterface
{
    private AuthenticationManagerInterface $authenticationManager;

    private LoggerInterface $logger;

    private NetworkCheckerInterface $networkChecker;

    private ServerRequestCreatorInterface $serverRequestCreator;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        AuthenticationManagerInterface $authenticationManager,
        LoggerInterface $logger,
        NetworkCheckerInterface $networkChecker,
        ServerRequestCreatorInterface $serverRequestCreator,
        UserRepositoryInterface $userRepository
    ) {
        $this->authenticationManager = $authenticationManager;
        $this->logger                = $logger;
        $this->networkChecker        = $networkChecker;
        $this->serverRequestCreator  = $serverRequestCreator;
        $this->userRepository        = $userRepository;
    }

    public function run(): void
    {
        if (!AmpConfig::get('subsonic_backend')) {
            echo T_("Disabled");

            return;
        }

        $request = $this->serverRequestCreator->fromGlobals();
        $request = $request->withQueryParams($request->getQueryParams());

        $gatekeeper = new Gatekeeper(
            $this->userRepository,
            $request,
            $this->logger
        );

        $query = $request->getQueryParams();
        $post  = (array)$request->getParsedBody();

        //$this->logger->debug(print_r($query, true), [LegacyLogger::CONTEXT_TYPE => self::class]);
        //$this->logger->debug(print_r(apache_request_headers(), true), [LegacyLogger::CONTEXT_TYPE => self::class]);

        $action = strtolower($post['ssaction'] ?? $query['ssaction'] ?? '');
        // Compatibility reason
        if (empty($action)) {
            $action = strtolower($post['action'] ?? $query['action'] ?? '');
        }

        $format = (string)($post['f'] ?? $query['f'] ?? 'xml');

        // Set the correct default headers
        self::_setHeaders($action, $format, (string)AmpConfig::get('site_charset', 'UTF-8'));

        // If we don't even have access control on then we can't use this!
        if (!AmpConfig::get('access_control')) {
            $this->logger->warning(
                'Error Attempted to use Subsonic API with Access Control turned off',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            ob_end_clean();
            Subsonic_Api::error($query, Subsonic_Api::SSERROR_UNAUTHORIZED, $action);

            return;
        }

        // Legacy Subsonic API by default.
        $subsonic_legacy = AmpConfig::get('subsonic_legacy', true); // force this for the moment to always use subsonic

        // Authenticate the user with preemptive HTTP Basic authentication first
        $userName = $post['PHP_AUTH_USER'] ?? $query['PHP_AUTH_USER'] ?? '';
        if (empty($userName)) {
            $userName = $post['u'] ?? $query['u'] ?? '';
        }
        $password = $post['PHP_AUTH_PW'] ?? $query['PHP_AUTH_PW'] ?? '';
        if (empty($password)) {
            $password = $post['p'] ?? $query['p'] ?? '';
        }


        $token     = $post['t'] ?? $query['t'] ?? '';
        $salt      = $post['s'] ?? $query['s'] ?? '';
        $version   = $post['v'] ?? $query['v'] ?? '';
        $clientapp = $post['c'] ?? $query['c'] ?? '';

        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = $clientapp;
        }

        $login      = false;
        $token_auth = (!empty($token) && !empty($salt));
        $api_auth   = false;
        $pass_auth  = (!empty($password) && !$token_auth);

        // apiKey authentication https://opensubsonic.netlify.app/docs/extensions/apikeyauth/
        $apiKey = $gatekeeper->getAuth('apiKey');
        if ($apiKey) {
            $user = $gatekeeper->getUser('apiKey');
            if ($user) {
                $login    = true;
                $userName = $user->getUsername();
                $api_auth = (!empty($userName));
                // get the user preference in case the server is different
                $subsonic_legacy = Preference::get_by_user($user->getId(), 'subsonic_legacy');
            }
        }


        // make sure we have correct authentication parameters
        if (
            empty($userName) ||
            empty($version) ||
            empty($action) ||
            empty($clientapp)
        ) {
            ob_end_clean();
            $this->logger->warning(
                'Missing Subsonic base parameters',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            if ($subsonic_legacy) {
                Subsonic_Api::error($query, Subsonic_Api::SSERROR_MISSINGPARAM, $action);
            } else {
                OpenSubsonic_Api::error($query, OpenSubsonic_Api::SSERROR_MISSINGPARAM, $action);
            }

            return;
        }

        if (
            !$token_auth &&
            !$api_auth &&
            !$pass_auth
        ) {
            $this->logger->warning(
                'Error Invalid Authentication attempt to Subsonic API',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            if ($subsonic_legacy) {
                Subsonic_Api::error($query, Subsonic_Api::SSERROR_BADAUTH, $action);
            } elseif ($apiKey) {
                OpenSubsonic_Api::error($query, OpenSubsonic_Api::SSERROR_BADAPIKEY, $action);
            } else {
                OpenSubsonic_Api::error($query, OpenSubsonic_Api::SSERROR_BADAUTH, $action);
            }

            return;
        }

        // Decode hex-encoded password
        $password = self::decryptPassword($password);

        if (!isset($user)) {
            // Check user authentication
            $auth = $this->authenticationManager->tokenLogin($userName, $token, $salt);
            if ($auth === []) {
                $auth = $this->authenticationManager->login($userName, $password, true);
            }
            $login = (bool)$auth['success'];
            $user  = User::get_from_username($userName);
        }

        if ($user === null || $login === false) {
            $this->logger->warning(
                'Invalid authentication attempt to Subsonic API for user [' . $userName . ']',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            ob_end_clean();
            if ($subsonic_legacy) {
                Subsonic_Api::error($query, Subsonic_Api::SSERROR_BADAUTH, $action);
            } elseif ($apiKey) {
                OpenSubsonic_Api::error($query, OpenSubsonic_Api::SSERROR_BADAPIKEY, $action);
            } else {
                OpenSubsonic_Api::error($query, OpenSubsonic_Api::SSERROR_BADAUTH, $action);
            }

            return;
        }

        Session::createGlobalUser($user);

        if (!$this->networkChecker->check(AccessTypeEnum::API, $user->id, AccessLevelEnum::GUEST)) {
            $this->logger->warning(
                'Unauthorized access attempt to Subsonic API [' . filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) . ']',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            ob_end_clean();
            Subsonic_Api::error($query, Subsonic_Api::SSERROR_UNAUTHORIZED, $action);

            return;
        }

        // Check server version
        if (
            version_compare(Subsonic_Api::API_VERSION, $version) < 0 &&
            !($clientapp == 'Sublime Music' && $version == '1.15.0')
        ) {
            ob_end_clean();
            $this->logger->warning(
                'Requested client version is not supported',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            Subsonic_Api::error($query, Subsonic_Api::SSERROR_APIVERSION_CLIENT, $action);

            return;
        }

        Preference::init();

        // get the user preference in case the server is different
        $subsonic_legacy = Preference::get_by_user($user->getId(), 'subsonic_legacy');

        // Get the list of possible methods for the Ampache API
        $os_methods = ($subsonic_legacy)
            ? []
            : array_diff(get_class_methods(OpenSubsonic_Api::class), OpenSubsonic_Api::SYSTEM_LIST);
        // allow fallback to a pure Subsonic 1.16.1 API
        $methods = ($subsonic_legacy)
            ? array_diff(get_class_methods(Subsonic_Api::class), Subsonic_Api::SYSTEM_LIST)
            : [];

        // Call your function if it's valid
        if (
            $os_methods !== [] &&
            in_array(strtolower($action), $os_methods) &&
            method_exists(OpenSubsonic_Api::class, $action)
        ) {
            call_user_func([OpenSubsonic_Api::class, $action], $input, $user);

            return;
        }
        if (
            $methods !== [] &&
            in_array(strtolower($action), $methods) &&
            method_exists(Subsonic_Api::class, $action)
        ) {
            call_user_func([Subsonic_Api::class, $action], $input, $user);

            // We only allow a single function to be called, and we assume it's cleaned up!
            return;
        }

        // If we manage to get here, we still need to hand out an XML document
        ob_end_clean();
        $this->logger->warning(
            sprintf('Bad function call %s', $action),
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );
        if ($subsonic_legacy) {
            Subsonic_Api::error($input, Subsonic_Api::SSERROR_APIVERSION_SERVER, $action);
        } else {
            OpenSubsonic_Api::error($input, OpenSubsonic_Api::SSERROR_APIVERSION_SERVER, $action);
        }
    }

    public static function decryptPassword(string $password): string
    {
        $encpwd = strpos($password, "enc:");
        if ($encpwd !== false) {
            $hex    = substr($password, 4);
            $decpwd = '';
            for ($count = 0; $count < strlen((string)$hex); $count += 2) {
                $decpwd .= chr((int)hexdec(substr($hex, $count, 2)));
            }

            return $decpwd;
        }

        return $password;
    }

    private static function _setHeaders(string $action, string $format, string $site_charset): void
    {
        if (!in_array($action, ['getcoverart', 'hls', 'stream', 'download', 'getavatar'])) {
            if (strtolower($format) == "json") {
                header("Content-type: application/json; charset=" . $site_charset);
            } elseif (strtolower($format) == "jsonp") {
                header("Content-type: text/javascript; charset=" . $site_charset);
            } else {
                header("Content-type: text/xml; charset=" . $site_charset);
            }
            header("Access-Control-Allow-Origin: *");
        }
    }
}
