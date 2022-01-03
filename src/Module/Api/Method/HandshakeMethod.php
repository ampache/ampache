<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Session;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class HandshakeMethod
 * @package Lib\ApiMethods
 */
final class HandshakeMethod
{
    public const ACTION = 'handshake';

    /**
     * handshake
     * MINIMUM_API_VERSION=380001
     *
     * This is the function that handles verifying a new handshake
     * Takes a timestamp, auth key, and username.
     *
     * @param array $input
     * auth      = (string) $passphrase
     * user      = (string) $username //optional
     * timestamp = (integer) UNIXTIME() //Required if login/password authentication
     * version   = (string) $version //optional
     * @return boolean
     */
    public static function handshake(array $input): bool
    {
        $now_time   = time();
        $timestamp  = preg_replace('/[^0-9]/', '', $input['timestamp'] ?? $now_time);
        $passphrase = $input['auth'];
        if (empty($passphrase)) {
            $passphrase = Core::get_post('auth');
        }
        $username = trim((string) ($input['user'] ?? Session::username($_REQUEST['auth'])));
        $user_ip  = Core::get_user_ip();
        // set the version to the old string for old api clients
        $version      = (isset($input['version'])) ? $input['version'] : Api::$version;
        Api::$version = ((int)$version >= 350001) ? Api::$version_numeric : Api::$version;
        $data_version = (int)substr($version, 0, 1);

        // Version check shouldn't be soo restrictive... only check with initial version to not break clients compatibility
        if ((int)($version) < Api::$auth_version && $data_version !== 5) {
            debug_event(self::class, 'Login Failed: Version too old', 1);
            AmpError::add('api', T_('Login failed, API version is too old'));

            return false;
        }

        $user_id = -1;
        // Grab the correct userid
        if (!$username) {
            $client = static::getUserRepository()->findByApiKey(trim($passphrase));
            if ($client) {
                $user_id = $client->id;
            }
            $username = false;
        } else {
            $client  = User::get_from_username($username);
            $user_id = $client->id;
        }

        // Log this attempt
        debug_event(self::class, "Login$data_version Attempt, IP: $user_ip Time: $timestamp User: $username ($user_id)", 1);

        // @todo replace by constructor injection
        global $dic;
        $networkAccessChecker = $dic->get(NetworkCheckerInterface::class);

        if (
            $user_id > 0 && $networkAccessChecker->check(AccessLevelEnum::TYPE_API, $user_id, AccessLevelEnum::LEVEL_GUEST)
        ) {
            // Authentication with user/password, we still need to check the password
            if ($username) {
                // If the timestamp isn't within 30 minutes sucks to be them
                if (($timestamp < ($now_time - 1800)) ||
                    ($timestamp > ($now_time + 1800))) {
                    debug_event(self::class, 'Login Failed: timestamp out of range ' . $timestamp . '/' . $now_time, 1);
                    AmpError::add('api', T_('Login failed, timestamp is out of range'));
                    Api::error(T_('Received Invalid Handshake') . ' - ' . T_('Login failed, timestamp is out of range'), '4701', self::ACTION, 'account', $input['api_format']);

                    return false;
                }

                // Now we're sure that there is an ACL line that matches this user or ALL USERS, pull the user's password and then see what we come out with
                $realpwd = static::getUserRepository()->retrievePasswordFromUser($client->getId());

                if (!$realpwd) {
                    debug_event(self::class, 'Unable to find user with userid of ' . $user_id, 1);
                    AmpError::add('api', T_('Incorrect username or password'));
                    Api::error(T_('Received Invalid Handshake') . ' - ' . T_('Login failed, timestamp is out of range'), '4701', self::ACTION, 'account', $input['api_format']);

                    return false;
                }

                $sha1pass = hash('sha256', $timestamp . $realpwd);

                if ($sha1pass !== $passphrase) {
                    $client = null;
                }
            }

            if ($client) {
                // Create the session
                $data             = array();
                $data['username'] = $client->username;
                $data['type']     = 'api';
                $data['apikey']   = $client->apikey;
                $data['value']    = $data_version;
                if (isset($input['client'])) {
                    $data['agent'] = filter_var($input['client'], FILTER_SANITIZE_STRING);
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
                //Session might not exist or has expired
                if (!Session::read($data['apikey'])) {
                    Session::destroy($data['apikey']);
                    $token = Session::create($data);
                } else {
                    Session::extend($data['apikey']);
                    $token = $data['apikey'];
                }

                debug_event(self::class, 'Login Success, passphrase matched', 1);
                $outarray = Api::server_details($token);

                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($outarray, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo Xml_Data::keyed_array($outarray);
                }

                return true;
            } // match
        } // end while

        debug_event(self::class, 'Login Failed, unable to match passphrase', 1);
        Api::error(T_('Received Invalid Handshake') . ' - ' . T_('Incorrect username or password'), '4701', self::ACTION, 'account', $input['api_format']);

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
}
