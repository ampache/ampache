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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Module\User\Tracking\UserTrackerInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Class PingMethod
 */
final class Ping4Method
{
    public const ACTION = 'ping';

    /**
     * ping
     * MINIMUM_API_VERSION=380001
     *
     * This can be called without being authenticated, it is useful for determining if what the status
     * of the server is, and what version it is running/compatible with
     *
     * @param array $input
     * auth = (string) //optional
     */
    public static function ping(array $input): void
    {
        $version      = (isset($input['version'])) ? $input['version'] : Api4::$version;
        $data_version = (int)substr($version, 0, 1);
        $results      = [
            'server' => AmpConfig::get('version'),
            'version' => Api4::$version,
            'compatible' => '350001'
        ];

        // Check and see if we should extend the api sessions (done if valid session is passed)
        if (array_key_exists('auth', $input) && Session::exists(AccessTypeEnum::API->value, $input['auth'])) {
            Session::extend($input['auth'], AccessTypeEnum::API->value);
            // perpetual sessions do not expire
            $perpetual      = (bool)AmpConfig::get('perpetual_api_session', false);
            $session_expire = ($perpetual)
                ? 0
                : date("c", time() + (int)AmpConfig::get('session_length', 3600) - 60);
            if (in_array($data_version, Api::API_VERSIONS)) {
                Session::write($input['auth'], $data_version, $perpetual);
            }
            $results = array_merge(
                ['session_expire' => $session_expire],
                $results
            );
            // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
            $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
            $db_results = Dba::read($sql);
            $row        = Dba::fetch_assoc($db_results);
            // Now we need to quickly get the totals
            $user   = User::get_from_username(Session::username($input['auth']));
            $counts = Catalog::get_server_counts($user->id ?? 0);

            // now add it all together
            $countarray = [
                'api' => Api4::$version,
                'session_expire' => $session_expire,
                'update' => date("c", (int)$row['update']),
                'add' => date("c", (int)$row['add']),
                'clean' => date("c", (int)$row['clean']),
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
            $results = array_merge(
                $results,
                $countarray
            );

            $user = static::getUserRepository()->findByApiKey($input['auth']);

            // We're about to start. Record this user's IP.
            if (AmpConfig::get('track_user_ip') && $user instanceof User) {
                static::getUserTracker()->trackIpAddress($user);
            }
        }

        debug_event(self::class, "Ping$data_version Received from " . filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP), 5);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($results, JSON_PRETTY_PRINT);
                break;
            default:
                echo Xml4_Data::keyed_array($results);
        }
    }

    /**
     * @todo replace by constructor injection
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
