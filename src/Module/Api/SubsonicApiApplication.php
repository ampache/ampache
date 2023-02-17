<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Api;

use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;

final class SubsonicApiApplication implements ApiApplicationInterface
{
    private AuthenticationManagerInterface $authenticationManager;

    private NetworkCheckerInterface $networkChecker;

    public function __construct(
        AuthenticationManagerInterface $authenticationManager,
        NetworkCheckerInterface $networkChecker
    ) {
        $this->authenticationManager = $authenticationManager;
        $this->networkChecker        = $networkChecker;
    }

    public function run(): void
    {
        if (!AmpConfig::get('subsonic_backend')) {
            echo T_("Disabled");

            return;
        }

        $action = strtolower($_REQUEST['ssaction'] ?? '');
        // Compatibility reason
        if (empty($action)) {
            $action = strtolower($_REQUEST['action'] ?? '');
        }
        $format   = ($_REQUEST['f']) ?? 'xml';
        $callback = $_REQUEST['callback'] ?? $format;
        /* Set the correct default headers */
        if ($action != "getcoverart" && $action != "hls" && $action != "stream" && $action != "download" && $action != "getavatar") {
            Subsonic_Api::_setHeader($format);
        }

        // If we don't even have access control on then we can't use this!
        if (!AmpConfig::get('access_control')) {
            debug_event('rest/index', 'Error Attempted to use Subsonic API with Access Control turned off', 3);
            ob_end_clean();
            Subsonic_Api::_apiOutput2($format, Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, $action));

            return;
        }

        // Authenticate the user with preemptive HTTP Basic authentication first
        $userName = $_REQUEST['PHP_AUTH_USER'] ?? '';
        if (empty($userName)) {
            $userName = $_REQUEST['u'] ?? '';
        }
        $password = $_REQUEST['PHP_AUTH_PW'] ?? '';
        if (empty($password)) {
            $password = $_REQUEST['p'] ?? '';
        }

        $token     = $_REQUEST['t'] ?? '';
        $salt      = $_REQUEST['s'] ?? '';
        $version   = $_REQUEST['v'] ?? '';
        $clientapp = $_REQUEST['c'] ?? '';

        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = $clientapp;
        }

        if (empty($userName) || (empty($password) && (empty($token) || empty($salt))) || empty($version) || empty($action) || empty($clientapp)) {
            ob_end_clean();
            debug_event('rest/index', 'Missing Subsonic base parameters', 3);
            Subsonic_Api::_apiOutput2($format, Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, $action), $callback);

            return;
        }

        $password = Subsonic_Api::_decryptPassword($password);

        // Check user authentication
        $auth = $this->authenticationManager->tokenLogin($userName, $token, $salt);
        if ($auth === []) {
            $auth = $this->authenticationManager->login($userName, $password, true);
        }
        if (!$auth['success']) {
            debug_event('rest/index', 'Invalid authentication attempt to Subsonic API for user [' . $userName . ']', 3);
            ob_end_clean();
            Subsonic_Api::_apiOutput2($format, Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_BADAUTH, $action), $callback);

            return;
        }

        $user = User::get_from_username($userName);
        Session::createGlobalUser($user);

        if (!$this->networkChecker->check(AccessLevelEnum::TYPE_API, $user->id, AccessLevelEnum::LEVEL_GUEST)) {
            debug_event('rest/index', 'Unauthorized access attempt to Subsonic API [' . filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) . ']', 3);
            ob_end_clean();
            Subsonic_Api::_apiOutput2($format, Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, $action), $callback);

            return;
        }

        // Check server version
        if (
            version_compare(Subsonic_Xml_Data::API_VERSION, $version) < 0 &&
            !($clientapp == 'Sublime Music' && $version == '1.15.0')
        ) {
            ob_end_clean();
            debug_event('rest/index', 'Requested client version is not supported', 3);
            Subsonic_Api::_apiOutput2($format, Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_APIVERSION_SERVER, $action), $callback);

            return;
        }
        Preference::init();

        // Define list of internal functions that should be skipped
        $internal_functions = array('_check_parameter', '_decrypt_password', '_follow_stream', '_updatePlaylist', '_setStar', '_setHeader', '_apiOutput', '_apiOutput2', '_xml2json');

        // Get the list of possible methods for the Ampache API
        $methods = array_diff(get_class_methods(Subsonic_Api::class), $internal_functions);

        // We do not use $_GET because of multiple parameters with the same name
        $query_string = $_SERVER['QUERY_STRING'];
        // Trick to avoid $HTTP_RAW_POST_DATA
        $postdata = file_get_contents("php://input");
        if (!empty($postdata)) {
            $query_string .= '&' . $postdata;
        }
        $query  = explode('&', $query_string);
        $params = array();
        foreach ($query as $param) {
            $decname  = false;
            $decvalue = false;
            if (strpos((string)$param, '=')) {
                [$name, $value] = explode('=', $param);
                $decname        = urldecode($name);
                $decvalue       = urldecode($value);
            }
            if (!$decname && !$decvalue) {
                continue;
            }

            // workaround for clementine/Qt5 bug
            // see https://github.com/clementine-player/Clementine/issues/6080
            $matches = array();
            if ($decname == "id" && preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $decvalue, $matches)) {
                $calc = (($matches[1] << 24) + ($matches[2] << 16) + ($matches[3] << 8) + $matches[4]);
                if ($calc) {
                    debug_event('rest/index', "Got id parameter $decvalue, which looks like an IP address. This is a known bug in some players, rewriting it to $calc", 4);
                    $decvalue = $calc;
                } else {
                    debug_event('rest/index', "Got id parameter $decvalue, which looks like an IP address. Recalculation of the correct id failed, though", 3);
                }
            }

            if (array_key_exists($decname, $params)) {
                if (!is_array($params[$decname])) {
                    $oldvalue           = $params[$decname];
                    $params[$decname]   = array();
                    $params[$decname][] = $oldvalue;
                }
                $params[$decname][] = $decvalue;
            } else {
                $params[$decname] = $decvalue;
            }
        }
        //debug_event('rest/index', print_r($params, true), 5);
        //debug_event('rest/index', print_r(apache_request_headers(), true), 5);

        // Call your function if it's valid
        if (in_array($action, $methods)) {
            call_user_func(array(Subsonic_Api::class, $action), $params);
            // We only allow a single function to be called, and we assume it's cleaned up!
            return;
        }

        // If we manage to get here, we still need to hand out an XML document
        ob_end_clean();
        Subsonic_Api::_apiOutput2($format, Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, $action), $callback);
    }
}
