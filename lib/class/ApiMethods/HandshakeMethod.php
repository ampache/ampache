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

namespace Lib\ApiMethods;

use Access;
use AmpConfig;
use AmpError;
use Api;
use Catalog;
use Core;
use Dba;
use Session;
use User;
use XML_Data;

final class HandshakeMethod
{
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
    public static function handshake($input)
    {
        $timestamp  = preg_replace('/[^0-9]/', '', $input['timestamp']);
        $passphrase = $input['auth'];
        if (empty($passphrase)) {
            $passphrase = Core::get_post('auth');
        }
        $username = trim((string) $input['user']);
        $user_ip  = Core::get_user_ip();
        $version  = (isset($input['version'])) ? $input['version'] : Api::$version;

        // Log the attempt
        debug_event('api.class', "Handshake Attempt, IP:$user_ip User:$username Version:$version", 5);

        // Version check shouldn't be soo restrictive... only check with initial version to not break clients compatibility
        if ((int) ($version) < Api::$auth_version) {
            debug_event('api.class', 'Login Failed: Version too old', 1);
            AmpError::add('api', T_('Login failed, API version is too old'));

            return false;
        }

        $user_id = -1;
        // Grab the correct userid
        if (!$username) {
            $client = User::get_from_apikey($passphrase);
            if ($client) {
                $user_id = $client->id;
            }
        } else {
            $client  = User::get_from_username($username);
            $user_id = $client->id;
        }

        // Log this attempt
        debug_event('api.class', "Login Attempt, IP:$user_ip Time: $timestamp User:$username ($user_id) Auth:$passphrase", 1);

        if ($user_id > 0 && Access::check_network('api', $user_id, 5)) {
            // Authentication with user/password, we still need to check the password
            if ($username) {
                // If the timestamp isn't within 30 minutes sucks to be them
                if (($timestamp < (time() - 1800)) ||
                    ($timestamp > (time() + 1800))) {
                    debug_event('api.class', 'Login failed, timestamp is out of range ' . $timestamp . '/' . time(), 1);
                    AmpError::add('api', T_('Login failed, timestamp is out of range'));
                    Api::message('error', T_('Received Invalid Handshake') . ' - ' . T_('Login failed, timestamp is out of range'), '401', $input['api_format']);

                    return false;
                }

                // Now we're sure that there is an ACL line that matches
                // this user or ALL USERS, pull the user's password and
                // then see what we come out with
                $realpwd = $client->get_password();

                if (!$realpwd) {
                    debug_event('api.class', 'Unable to find user with userid of ' . $user_id, 1);
                    AmpError::add('api', T_('Incorrect username or password'));
                    Api::message('error', T_('Received Invalid Handshake') . ' - ' . T_('Login failed, timestamp is out of range'), '401', $input['api_format']);

                    return false;
                }

                $sha1pass = hash('sha256', $timestamp . $realpwd);

                if ($sha1pass !== $passphrase) {
                    $client = null;
                }
            } else {
                $timestamp = time();
            }

            if ($client) {
                // Create the session
                $data             = array();
                $data['username'] = $client->username;
                $data['type']     = 'api';
                $data['apikey']   = $client->apikey;
                $data['value']    = $timestamp;
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
                //Session might not exist or has expired
                //
                if (!Session::read($data['apikey'])) {
                    Session::destroy($data['apikey']);
                    $token = Session::create($data);
                } else {
                    Session::extend($data['apikey']);
                    $token = $data['apikey'];
                }

                debug_event('api.class', 'Login Success, passphrase matched', 1);

                // We need to also get the 'last update' of the
                // catalog information in an RFC 2822 Format
                $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
                $db_results = Dba::read($sql);
                $row        = Dba::fetch_assoc($db_results);

                // Now we need to quickly get the totals
                $counts = Catalog::count_server(true);

                // send the totals
                $outarray = array('auth' => $token,
                    'api' => Api::$version,
                    'session_expire' => date("c", time() + AmpConfig::get('session_length') - 60),
                    'update' => date("c", (int) $row['update']),
                    'add' => date("c", (int) $row['add']),
                    'clean' => date("c", (int) $row['clean']),
                    'songs' => (int) $counts['song'],
                    'albums' => (int) $counts['album'],
                    'artists' => (int) $counts['artist'],
                    'playlists' => ((int) $counts['playlist'] + (int) $counts['search']),
                    'videos' => (int) $counts['video'],
                    'catalogs' => (int) $counts['catalog']);
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($outarray, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo XML_Data::keyed_array($outarray);
                }

                return true;
            } // match
        } // end while

        debug_event('api.class', 'Login Failed, unable to match passphrase', 1);
        Api::message('error', T_('Received Invalid Handshake') . ' - ' . T_('Incorrect username or password'), '401', $input['api_format']);

        return false;
    }
}
