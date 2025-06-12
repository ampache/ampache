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

namespace Ampache\Module\Api\Method\Api3;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\User\Tracking\UserTrackerInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api3;
use Ampache\Module\Api\Xml3_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Session;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class Handshake3Method
 */
final class Handshake3Method
{
    public const ACTION = 'handshake';

    /**
     * handshake
     *
     * This is the function that handles verifying a new handshake
     * Takes a timestamp, auth key, and username.
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
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function handshake(array $input): bool
    {
        $now_time   = time();
        $timestamp  = (int)preg_replace('/[^0-9]/', '', (string)($input['timestamp'] ?? $now_time));
        $passphrase = $input['auth'];
        if (empty($passphrase)) {
            $passphrase = $_POST['auth'];
        }
        $username     = trim((string) ($input['user'] ?? Session::username($passphrase)));
        $user_ip      = Core::get_user_ip();
        $version      = (isset($input['version'])) ? (string) $input['version'] : Api3::$version;
        $data_version = (int)substr($version, 0, 1);

        // Version check shouldn't be soo restrictive... only check with initial version to not break clients compatibility
        if ((int)$version < Api3::$auth_version) {
            debug_event(self::class, 'Login Failed: version too old', 1);
            AmpError::add('api', T_('Login Failed: version too old'));

            return false;
        }

        $exists  = false;
        $user_id = -1;
        // Grab the correct userid
        if (!$username) {
            $client   = self::getUserRepository()->findByApiKey(trim($passphrase));
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

        // @todo replace by constructor injection
        global $dic;
        $networkAccessChecker = $dic->get(NetworkCheckerInterface::class);

        if ($user_id > 0 && $networkAccessChecker->check(AccessTypeEnum::API, $user_id, AccessLevelEnum::GUEST)) {
            // Authentication with user/password, we still need to check the password
            if ($username) {
                // If the timestamp isn't within 30 minutes sucks to be them
                if (
                    ($timestamp < ($now_time - 1800)) ||
                    ($timestamp > ($now_time + 1800))
                ) {
                    debug_event(self::class, 'Login Failed: timestamp out of range ' . $timestamp . '/' . $now_time, 1);
                    AmpError::add('api', T_('Login Failed: timestamp out of range'));
                    echo Xml3_Data::error(401, T_('Error Invalid Handshake - ') . T_('Login Failed: timestamp out of range'));

                    return false;
                }

                // Now we're sure that there is an ACL line that matches this user or ALL USERS, pull the user's password and then see what we come out with
                $realpwd = self::getUserRepository()->retrievePasswordFromUser($client->getId());

                if (!$realpwd) {
                    debug_event(self::class, 'Unable to find user with userid of ' . $user_id, 1);
                    AmpError::add('api', T_('Invalid Username/Password'));
                    echo Xml3_Data::error(401, T_('Error Invalid Handshake - ') . T_('Invalid Username/Password'));

                    return false;
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
                    $data['username'] = (string)$client->username;
                    $data['type']     = 'api';
                    $data['apikey']   = (string)$client->apikey;
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
                    self::getUserTracker()->trackIpAddress($client, 'handshake');
                }

                debug_event(self::class, 'Login Success, passphrase matched', 1);

                // We need to also get the 'last update' of the
                // catalog information in an RFC 2822 Format
                $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
                $db_results = Dba::read($sql);
                $row        = Dba::fetch_assoc($db_results);

                // Now we need to quickly get the totals
                $counts = Catalog::get_server_counts($user_id);
                // perpetual sessions do not expire
                $perpetual      = (bool)AmpConfig::get('perpetual_api_session', false);
                $session_expire = ($perpetual)
                    ? 0
                    : date("c", $now_time + AmpConfig::get('session_length') - 60);

                $results = [
                    'auth' => $token,
                    'api' => Api3::$version,
                    'session_expire' => $session_expire,
                    'update' => date("c", $row['update']),
                    'add' => date("c", $row['add']),
                    'clean' => date("c", $row['clean']),
                    'songs' => $counts['song'],
                    'albums' => $counts['album'],
                    'artists' => $counts['artist'],
                    'playlists' => $counts['playlist'],
                    'videos' => $counts['video'],
                    'catalogs' => $counts['catalog']
                ];
                echo Xml3_Data::keyed_array($results);

                return true;
            } // match
        } // end while

        debug_event(self::class, 'Login Failed, unable to match passphrase', 1);
        echo Xml3_Data::error(401, T_('Error Invalid Handshake - ') . T_('Invalid Username/Password'));

        return false;
    }

    /**
     * @deprecated inject by constructor
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getUserTracker(): UserTrackerInterface
    {
        global $dic;

        return $dic->get(UserTrackerInterface::class);
    }
}
