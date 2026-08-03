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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Module\User\Tracking\UserTrackerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

final class Handshake4Method implements MethodInterface
{
    public const string ACTION = 'handshake';

    public function __construct(
        private NetworkCheckerInterface $networkChecker,
        private UserRepositoryInterface $userRepository,
        private UserTrackerInterface $userTracker,
    ) {}

    /**
     * handshake
     * MINIMUM_API_VERSION=380001
     *
     * This is the function that handles verifying a new handshake
     * Takes a timestamp, auth key, and username.
     *
     * auth = (string) $passphrase
     * user = (string) $username //optional
     * timestamp = (integer) UNIXTIME() //Required if login/password authentication
     * version = (string) $version //optional
     *
     * @param array{
     *     user?: string,
     *     timestamp?: int,
     *     version?: string,
     *     client?: string,
     *     geo_latitude?: float,
     *     geo_longitude?: float,
     *     geo_name?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $now_time   = time();
        $timestamp  = (int) preg_replace('/[^0-9]/', '', (string) ($input['timestamp'] ?? $now_time));
        $passphrase = $input['auth'];
        if (empty($passphrase)) {
            $passphrase = Core::get_post('auth');
        }
        $username     = trim((string) ($input['user'] ?? Session::username($passphrase)));
        $user_ip      = Core::get_user_ip();
        $version      = (isset($input['version'])) ? (string) $input['version'] : Api4::$version;
        $data_version = (int) substr((string) $version, 0, 1);

        // Version check shouldn't be soo restrictive... only check with initial version to not break clients compatibility
        if ((int) ($version) < Api4::$auth_version) {
            debug_event(self::class, 'Login Failed: Version too old', 1);
            AmpError::add('api', 'Login failed, API version is too old');

            return $response;
        }

        $exists  = false;
        $user_id = -1;
        // Grab the correct userid
        if (!$username) {
            $client   = $this->userRepository->findByApiKey(trim($passphrase));
            $username = false;
        } elseif (Session::exists('api', $input['auth'])) {
            $client   = User::get_from_username($username);
            $username = false;
            $exists   = true;
        } else {
            $client = User::get_from_username($username);
        }
        if ($client instanceof User) {
            $user_id = $client->id;
        }

        // Log this attempt
        debug_event(self::class, "Login$data_version Attempt, IP: $user_ip Time: $timestamp User: " . ($client->username ?? '') . " ($user_id)", 1);

        if ($user_id > 0 && $this->networkChecker->check(AccessTypeEnum::API, $user_id, AccessLevelEnum::GUEST)) {
            // Authentication with user/password, we still need to check the password
            if ($username) {
                // If the timestamp isn't within 30 minutes sucks to be them
                if (
                    ($timestamp < ($now_time - 1800))
                    || ($timestamp > ($now_time + 1800))
                ) {
                    debug_event(self::class, 'Login Failed: timestamp out of range ' . $timestamp . '/' . $now_time, 1);
                    AmpError::add('api', 'Login Failed, timestamp is out of range');
                    Api4::message('error', 'Received Invalid Handshake' . ' - ' . 'Login failed, timestamp is out of range' . ' (timestamp: ' . $timestamp . ' ' . 'Server' . ': ' . $now_time . ')', '401', $input['api_format']);

                    return $response;
                }

                // Now we're sure that there is an ACL line that matches this user or ALL USERS, pull the user's password and then see what we come out with
                $realpwd = $this->userRepository->retrievePasswordFromUser($client?->getId() ?? 0);

                if (!$realpwd) {
                    debug_event(self::class, 'Unable to find user with userid of ' . $user_id, 1);
                    AmpError::add('api', 'Incorrect username or password');
                    Api4::message('error', 'Received Invalid Handshake' . ' - ' . 'Login failed, timestamp is out of range', '401', $input['api_format']);

                    return $response;
                }

                $sha1pass = hash('sha256', $timestamp . $realpwd);

                if ($sha1pass !== $passphrase) {
                    $client = null;
                }
            }

            if ($client instanceof User) {
                if ($exists) {
                    Session::extend($input['auth'], AccessTypeEnum::API->value);
                    $token = $input['auth'];
                } else {
                    // Create the session
                    $data             = [];
                    $data['username'] = (string) $client->username;
                    $data['type']     = 'api';
                    $data['apikey']   = (string) $client->apikey;
                    $data['value']    = $data_version;
                    if (isset($input['client'])) {
                        $data['agent'] = $input['client'];
                    }
                    if (isset($input['geo_latitude'])) {
                        $data['geo_latitude'] = $input['geo_latitude'];
                    }
                    if (isset($input['geo_longitude'])) {
                        $data['geo_longitude'] = $input['geo_longitude'];
                    }
                    if (isset($input['geo_name'])) {
                        $data['geo_name'] = $input['geo_name'];
                    }
                    // Session might not exist or has expired
                    if (!Session::read($data['apikey'])) {
                        Session::destroy($data['apikey']);
                        $token = Session::create($data);
                    } else {
                        Session::extend($data['apikey'], AccessTypeEnum::API->value);
                        $token = $data['apikey'];
                    }
                }

                // We're about to start. Record this user's IP.
                if (AmpConfig::get('track_user_ip')) {
                    $this->userTracker->trackIpAddress($client, 'handshake');
                }

                debug_event(self::class, 'Login Success, passphrase matched', 1);
                // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
                $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
                $db_results = Dba::read($sql);
                $row        = Dba::fetch_assoc($db_results);

                // Now we need to quickly get the totals
                $counts = Catalog::get_server_counts($user_id);
                // perpetual sessions do not expire
                $perpetual      = (bool) AmpConfig::get('perpetual_api_session', false);
                $session_expire = ($perpetual)
                    ? 0
                    : date("c", $now_time + AmpConfig::get('session_length') - 60);

                // send the totals
                $results = [
                    'auth' => $token,
                    'api' => Api4::$version,
                    'session_expire' => $session_expire,
                    'update' => date("c", (int) $row['update']),
                    'add' => date("c", (int) $row['add']),
                    'clean' => date("c", (int) $row['clean']),
                    'songs' => $counts['song'],
                    'albums' => $counts['album'],
                    'artists' => $counts['artist'],
                    'playlists' => ($counts['playlist'] + $counts['search']),
                    'videos' => $counts['video'],
                    'catalogs' => $counts['catalog'],
                    'users' => $counts['user'],
                    'tags' => $counts['tag'],
                    'podcasts' => $counts['podcast'],
                    'podcast_episodes' => $counts['podcast_episode'],
                    'shares' => $counts['share'],
                    'licenses' => $counts['license'],
                    'live_streams' => $counts['live_stream'],
                    'labels' => $counts['label']
                ];
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($results, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo Api::keyed_array($results);
                }

                return $response;
            } // match
        }

        debug_event(self::class, 'Login Failed, unable to match passphrase', 1);
        Api4::message('error', 'Received Invalid Handshake' . ' - ' . 'Incorrect username or password', '401', $input['api_format']);

        return $response;
    }
}
