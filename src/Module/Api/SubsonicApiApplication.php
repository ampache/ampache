<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Authentication\Gatekeeper;
use Ampache\Module\Authentication\AuthenticationManagerInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\System\Preference;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Log\LoggerInterface;

final class SubsonicApiApplication implements ApiApplicationInterface
{
    private AuthenticationManagerInterface $authenticationManager;
    private LoggerInterface $logger;
    private NetworkCheckerInterface $networkChecker;
    private OpenSubsonic_Api $openSubsonicApi;
    private ServerRequestCreatorInterface $serverRequestCreator;
    private Subsonic_Api $subsonicApi;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        AuthenticationManagerInterface $authenticationManager,
        LoggerInterface $logger,
        NetworkCheckerInterface $networkChecker,
        OpenSubsonic_Api $openSubsonicApi,
        ServerRequestCreatorInterface $serverRequestCreator,
        Subsonic_Api $subsonicApi,
        UserRepositoryInterface $userRepository,
    ) {
        $this->authenticationManager = $authenticationManager;
        $this->logger                = $logger;
        $this->networkChecker        = $networkChecker;
        $this->openSubsonicApi       = $openSubsonicApi;
        $this->serverRequestCreator  = $serverRequestCreator;
        $this->subsonicApi           = $subsonicApi;
        $this->userRepository        = $userRepository;
    }

    public static function decryptPassword(string $password): string
    {
        $encpwd = strpos($password, 'enc:');
        if ($encpwd === false) {
            return $password;
        }

        $hex = substr($password, 4);
        if (!ctype_xdigit($hex)) {
            return $password;
        }

        $decpwd = '';
        for ($count = 0; $count < strlen($hex); $count += 2) {
            $decpwd .= chr(hexdec(substr($hex, $count, 2)) & 0xFF);
        }

        return $decpwd;
    }

    /**
     * Parse a Subsonic/OpenSubsonic query into search tokens.
     *
     * Rules:
     * - Search only by `name`/`title` for the object type
     * - Split all words by space (` `) into individual (**OR**) search terms
     * - Search terms ending with `*`|`%` are prefix (**LIKE**) matched
     * - Wrap multiple words with quotes (`"`) to group them together
     * - Join multiple words with plus (`+`) to group them together
     * - Special characters (`*`|`%`) inside group strings are literal
     *
     * @return array<int, array{value: string, operator: int}>
     */
    public static function parseSearchQuery(string $query): array
    {
        $query = trim(html_entity_decode($query));
        if ($query === '') {
            return [];
        }

        preg_match_all('/"[^"]*"[*%]?|[^\\s"]+/', $query, $matches);

        $tokens = [];
        foreach ($matches[0] as $parts) {
            $part = trim($parts);
            if ($part === '' || $part === '+') {
                continue;
            }

            // Quoted literal equals: "foo"
            // Quoted literal starts with: "foo"*
            // Quoted literal starts with: "foo"%
            if (preg_match('/^"([^"]*)"([*%])?$/', $part, $quotedMatch) === 1) {
                $value = trim(preg_replace('/\\s+/', ' ', $quotedMatch[1]) ?? $quotedMatch[1]);

                if ($value !== '') {
                    $tokens[] = [
                        'value' => $value,
                        'operator' => (isset($quotedMatch[2]))
                            ? 2 // starts with
                            : 4 // equals
                    ];
                }

                continue;
            }

            // Outside quotes, plus joins into an exact group
            // example+search
            // example+sear*
            if (str_contains($part, '+')) {
                $operator = 4; // equals
                if (str_ends_with($part, '*') || str_ends_with($part, '%')) {
                    $part     = substr($part, 0, -1);
                    $operator = 0; // contains
                }

                $segments = array_values(array_filter(
                    array_map('trim', explode('+', $part)),
                    static fn(string $segment): bool => $segment !== ''
                ));

                if (count($segments) > 1) {
                    $tokens[] = [
                        'value' => implode(' ', $segments),
                        'operator' => $operator,
                    ];
                    continue;
                }

                if (count($segments) === 1) {
                    $part = $segments[0];
                } else {
                    continue;
                }
            }

            // Optional legacy suffix star for non-quoted plain tokens
            if (str_ends_with($part, '*') || str_ends_with($part, '%')) {
                $part = substr($part, 0, -1);
            }

            $value = trim(preg_replace('/\\s+/', ' ', $part) ?? $part);
            if ($value === '') {
                continue;
            }

            $value    = str_replace('*', '%', $value);
            $operator = (str_contains($value, '%'))
                ? 0 // contains
                : 2; // Starts with

            $tokens[] = [
                'value' => $value,
                'operator' => $operator,
            ];
        }

        return $tokens;
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

        $post = ($request->getMethod() === 'POST')
            ? (array) $request->getParsedBody()
            : [];

        $query = array_merge($request->getQueryParams(), $post);

        //$this->logger->debug(print_r($query, true), [LegacyLogger::CONTEXT_TYPE => self::class]);
        //$this->logger->debug(print_r(apache_request_headers(), true), [LegacyLogger::CONTEXT_TYPE => self::class]);

        $action = strtolower($query['ssaction'] ?? '');
        // Compatibility reason
        if (empty($action)) {
            $action = strtolower($query['action'] ?? '');
        }

        // The action is called as a method name, and `hls.m3u8` cannot be one; the suffix is there so a
        // client sees a playlist file name, and `hls()` is what serves it
        if ($action === 'hls.m3u8') {
            $action = 'hls';
        }

        $format = (string) ($query['f'] ?? 'xml');

        // Set the correct default headers
        self::_setHeaders($action, $format, (string) AmpConfig::get('site_charset', 'UTF-8'));

        // If we don't even have access control on then we can't use this!
        if (!AmpConfig::get('access_control')) {
            $this->logger->warning(
                'Error Attempted to use Subsonic API with Access Control turned off',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            ob_end_clean();
            $this->subsonicApi->error($query, Subsonic_Api::SSERROR_UNAUTHORIZED, $action);

            return;
        }

        // Legacy Subsonic API by default.
        $subsonic_legacy = AmpConfig::get('subsonic_legacy', true); // force this for the moment to always use subsonic

        // Authenticate the user with preemptive HTTP Basic authentication first
        $userName = $query['PHP_AUTH_USER'] ?? '';
        if (empty($userName)) {
            $userName = $query['u'] ?? '';
        }
        $password = $query['PHP_AUTH_PW'] ?? '';
        if (empty($password)) {
            $password = $query['p'] ?? '';
        }

        $token     = $query['t'] ?? '';
        $salt      = $query['s'] ?? '';
        $version   = $query['v'] ?? '';
        $clientapp = $query['c'] ?? '';

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
            empty($userName)
            || empty($version)
            || empty($action)
            || empty($clientapp)
        ) {
            ob_end_clean();
            $this->logger->warning(
                'Missing Subsonic base parameters',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            if ($subsonic_legacy) {
                $this->subsonicApi->error($query, Subsonic_Api::SSERROR_MISSINGPARAM, $action);
            } else {
                $this->openSubsonicApi->error($query, OpenSubsonic_Api::SSERROR_MISSINGPARAM, $action);
            }

            return;
        }

        if (
            !$token_auth
            && !$api_auth
            && !$pass_auth
        ) {
            $this->logger->warning(
                'Error Invalid Authentication attempt to Subsonic API',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            if ($subsonic_legacy) {
                $this->subsonicApi->error($query, Subsonic_Api::SSERROR_BADAUTH, $action);
            } elseif ($apiKey) {
                $this->openSubsonicApi->error($query, OpenSubsonic_Api::SSERROR_BADAPIKEY, $action);
            } else {
                $this->openSubsonicApi->error($query, OpenSubsonic_Api::SSERROR_BADAUTH, $action);
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
            $login = (bool) $auth['success'];
            $user  = User::get_from_username($userName);
        }

        if ($user === null || $login === false) {
            $this->logger->warning(
                'Invalid authentication attempt to Subsonic API for user [' . $userName . ']',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            ob_end_clean();
            if ($subsonic_legacy) {
                $this->subsonicApi->error($query, Subsonic_Api::SSERROR_BADAUTH, $action);
            } elseif ($apiKey) {
                $this->openSubsonicApi->error($query, OpenSubsonic_Api::SSERROR_BADAPIKEY, $action);
            } else {
                $this->openSubsonicApi->error($query, OpenSubsonic_Api::SSERROR_BADAUTH, $action);
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
            $this->subsonicApi->error($query, Subsonic_Api::SSERROR_UNAUTHORIZED, $action);

            return;
        }

        // Check server version
        if (
            version_compare(Subsonic_Api::API_VERSION, $version) < 0
            && !($clientapp == 'Sublime Music' && $version == '1.15.0')
        ) {
            ob_end_clean();
            $this->logger->warning(
                sprintf('Requested client version %s is newer than the supported %s', $version, Subsonic_Api::API_VERSION),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
            $this->subsonicApi->error($query, Subsonic_Api::SSERROR_APIVERSION_SERVER, $action);

            return;
        }

        Preference::init();

        // get the user preference in case the server is different
        $subsonic_legacy = Preference::get_by_user($user->getId(), 'subsonic_legacy');

        // Get the list of possible methods for the Ampache API. The action has already been lowercased, so the
        // handler names are folded to match: a camelCase method is otherwise unreachable through this gate.
        $os_methods = ($subsonic_legacy)
            ? []
            : array_map('strtolower', array_diff(get_class_methods($this->openSubsonicApi), OpenSubsonic_Api::SYSTEM_LIST));
        // allow fallback to a pure Subsonic 1.16.1 API
        $methods = ($subsonic_legacy)
            ? array_map('strtolower', array_diff(get_class_methods($this->subsonicApi), Subsonic_Api::SYSTEM_LIST))
            : [];

        // We do not use $_GET because of multiple parameters with the same name
        $query_string = (string) ($_SERVER['QUERY_STRING'] ?? '');
        // Trick to avoid $HTTP_RAW_POST_DATA
        $postdata = file_get_contents("php://input");
        $body     = null;
        if (!empty($postdata)) {
            // A JSON body carries a whole object rather than form pairs, so it is decoded aside instead of being
            // appended to the query string, which would split it on every `&` and `=` it happens to contain.
            if (str_contains(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
                $decoded = json_decode($postdata, true);
                $body    = (is_array($decoded)) ? $decoded : null;
            } else {
                $query_string .= '&' . $postdata;
            }
        }
        $query = explode('&', $query_string);
        $input = [];
        if ($body !== null) {
            $input['_body'] = $body;
        }
        foreach ($query as $param) {
            $decname  = false;
            $decvalue = false;
            if (strpos($param, '=')) {
                [$name, $value] = explode('=', $param);
                $decname        = urldecode($name);
                $decvalue       = urldecode($value);
            }
            if ($decname && $decvalue) {
                // workaround for clementine/Qt5 bug
                // see https://github.com/clementine-player/Clementine/issues/6080
                $matches = [];
                if ($decname == "id" && preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $decvalue, $matches)) {
                    $calc = (
                        (((int) $matches[1]) << 24)
                        + (((int) $matches[2]) << 16)
                        + (((int) $matches[3]) << 8)
                        + ((int) $matches[4])
                    );
                    if ($calc) {
                        $this->logger->notice(
                            "Got id parameter $decvalue, which looks like an IP address. This is a known bug in some players, rewriting it to $calc",
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                        $decvalue = $calc;
                    } else {
                        $this->logger->warning(
                            "Got id parameter $decvalue, which looks like an IP address. Recalculation of the correct id failed, though",
                            [LegacyLogger::CONTEXT_TYPE => self::class]
                        );
                    }
                }

                if (array_key_exists($decname, $input)) {
                    if (is_array($input[$decname]) === false) {
                        $oldvalue          = $input[$decname];
                        $input[$decname]   = [];
                        $input[$decname][] = $oldvalue;
                    }
                    $input[$decname][] = $decvalue;
                } else {
                    $input[$decname] = $decvalue;
                }
            }
        }

        //$this->logger->debug(print_r($input, true), [LegacyLogger::CONTEXT_TYPE => self::class]);
        //$this->logger->debug(print_r(apache_request_headers(), true), [LegacyLogger::CONTEXT_TYPE => self::class]);

        // Call your function if it's valid
        $callback = [$this->openSubsonicApi, $action];
        if (
            $os_methods !== []
            && in_array(strtolower($action), $os_methods)
            && method_exists($this->openSubsonicApi, $action)
            && assert(is_callable($callback))
        ) {
            call_user_func($callback, $input, $user);

            return;
        }
        $callback = [$this->subsonicApi, $action];
        if (
            $methods !== []
            && in_array(strtolower($action), $methods)
            && method_exists($this->subsonicApi, $action)
            && assert(is_callable($callback))
        ) {
            call_user_func($callback, $input, $user);

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
            $this->subsonicApi->error($input, Subsonic_Api::SSERROR_GENERIC, $action);
        } else {
            $this->openSubsonicApi->error($input, OpenSubsonic_Api::SSERROR_GENERIC, $action);
        }
    }
}
