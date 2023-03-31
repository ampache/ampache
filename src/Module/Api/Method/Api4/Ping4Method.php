<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Xml4_Data;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;

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
    public static function ping(array $input)
    {
        $version      = (isset($input['version'])) ? $input['version'] : Api4::$version;
        $data_version = (int)substr($version, 0, 1);
        $results      = array(
            'server' => AmpConfig::get('version'),
            'version' => Api4::$version,
            'compatible' => '350001'
        );

        // Check and see if we should extend the api sessions (done if valid session is passed)
        if (Session::exists('api', $input['auth'])) {
            Session::extend($input['auth']);
            if (in_array($data_version, array(3, 4, 5))) {
                Session::write($input['auth'], $data_version);
            }
            $results = array_merge(array('session_expire' => date("c", time() + (int)AmpConfig::get('session_length', 3600) - 60)), $results);
            // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
            $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
            $db_results = Dba::read($sql);
            $row        = Dba::fetch_assoc($db_results);
            // Now we need to quickly get the totals
            $user   = User::get_from_username(Session::username($input['auth']));
            $counts = Catalog::get_server_counts($user->id ?? 0);
            // now add it all together
            $countarray = array('api' => Api4::$version,
                'session_expire' => date("c", time() + AmpConfig::get('session_length') - 60),
                'update' => date("c", (int) $row['update']),
                'add' => date("c", (int) $row['add']),
                'clean' => date("c", (int) $row['clean']),
                'songs' => (int) $counts['song'],
                'albums' => (int) $counts['album'],
                'artists' => (int) $counts['artist'],
                'playlists' => ((int) $counts['playlist'] + (int) $counts['search']),
                'videos' => (int) $counts['video'],
                'catalogs' => (int) $counts['catalog'],
                'users' => (int) $counts['user'],
                'tags' => (int) $counts['tag'],
                'podcasts' => (int) $counts['podcast'],
                'podcast_episodes' => (int) $counts['podcast_episode'],
                'shares' => (int) $counts['share'],
                'licenses' => (int) $counts['license'],
                'live_streams' => (int) $counts['live_stream'],
                'labels' => (int) $counts['label']
            );
            $results = array_merge($results, $countarray);
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
    } // ping
}
