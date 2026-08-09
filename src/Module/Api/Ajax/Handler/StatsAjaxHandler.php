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

namespace Ampache\Module\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Gui\Stats\RecentlyPlayedMode;
use Ampache\Gui\Stats\RecentlyPlayedView;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Plugin\PluginRetrieverInterface;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\System\Session;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Plugin\PluginLocationInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

final readonly class StatsAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private PluginRetrieverInterface $pluginRetriever,
    ) {}

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'geolocation':
                if (AmpConfig::get('geolocation')) {
                    if ($user->id > 0) {
                        $name = $_REQUEST['name'] ?? null;
                        if (
                            empty($name)
                            && !empty($_REQUEST['latitude'])
                            && !empty($_REQUEST['longitude'])
                        ) {
                            $latitude  = (float) $_REQUEST['latitude'];
                            $longitude = (float) $_REQUEST['longitude'];
                            // First try to get from local cache (avoid external api requests)
                            $name = Stats::get_cached_place_name($latitude, $longitude);
                            if (in_array($name, [null, '', '0'], true)) {
                                foreach ($this->pluginRetriever->retrieveByType(PluginTypeEnum::GEO_LOCATION, $user) as $plugin) {
                                    $name = ($plugin->_plugin instanceof PluginLocationInterface)
                                        ? $plugin->_plugin->get_location_name($latitude, $longitude)
                                        : null;
                                    if (!in_array($name, [null, '', '0'], true)) {
                                        break;
                                    }
                                }
                            }

                            // Better to check for bugged values here and keep previous user good location
                            // Someone listing music at 0.0,0.0 location would need a waterproof music player btw
                            if (
                                !in_array($name, [null, '', '0'], true)
                                && $latitude > 0
                                && $longitude > 0
                            ) {
                                Session::update_geolocation((string) session_id(), $latitude, $longitude, $name);
                            }
                        }
                    }
                } else {
                    debug_event('stats.ajax', 'Geolocation not enabled for the user.', 3);
                }

                break;
            case 'delete_play':
                if (
                    check_http_referer() === true
                    && Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
                    && isset($_REQUEST['activity_id'])
                ) {
                    Stats::delete((int) $_REQUEST['activity_id']);
                }

                ob_start();
                show_now_playing();
                $results['now_playing'] = ob_get_clean();
                ob_start();
                $user_id = (isset($_REQUEST['user_id']))
                    ? (int) $this->requestParser->getFromRequest('user_id')
                    : ($user->id ?: -1);
                $user_only = isset($_REQUEST['user_only']);
                $all_types = (bool) AmpConfig::get('home_recently_played_all');
                $data      = ($all_types)
                    ? Stats::get_recently_played($user_id, 'stream', null, $user_only)
                    : Stats::get_recently_played($user_id, 'stream', 'song', $user_only);
                if (!$all_types) {
                    Song::build_cache(array_keys($data));
                }

                echo (new RecentlyPlayedView(
                    ($all_types) ? RecentlyPlayedMode::ALL_TYPES : RecentlyPlayedMode::SONGS,
                    $data,
                    $user,
                    $user_id,
                    $user_only,
                    AmpConfig::get_web_path('/client')
                ))->render();

                $results['recently_played'] = ob_get_clean();
                break;
            case 'delete_skip':
                if (
                    check_http_referer() === true
                    && Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
                    && isset($_REQUEST['activity_id'])
                ) {
                    Stats::delete((int) $_REQUEST['activity_id']);
                }

                ob_start();
                show_now_playing();
                $results['now_playing'] = ob_get_clean();
                ob_start();
                $user_id = (isset($_REQUEST['user_id']))
                    ? (int) $this->requestParser->getFromRequest('user_id')
                    : ($user->id ?: -1);
                $user_only = isset($_REQUEST['user_only']);
                $data      = Stats::get_recently_played($user_id, 'skip', 'song', $user_only);
                Song::build_cache(array_keys($data));
                echo (new RecentlyPlayedView(
                    RecentlyPlayedMode::SKIPPED,
                    $data,
                    $user,
                    $user_id,
                    $user_only,
                    AmpConfig::get_web_path('/client')
                ))->render();
                $results['recently_skipped'] = ob_get_clean();
                break;
            case 'refresh_skipped':
                ob_start();
                show_now_playing();
                $results['now_playing'] = ob_get_clean();
                ob_start();
                $user_id = (isset($_REQUEST['user_id']))
                    ? (int) $this->requestParser->getFromRequest('user_id')
                    : ($user->id ?: -1);
                $user_only = isset($_REQUEST['user_only']);
                $data      = Stats::get_recently_played($user_id, 'skip', 'song', $user_only);
                Song::build_cache(array_keys($data));
                echo (new RecentlyPlayedView(
                    RecentlyPlayedMode::SKIPPED,
                    $data,
                    $user,
                    $user_id,
                    $user_only,
                    AmpConfig::get_web_path('/client')
                ))->render();
                $results['recently_skipped'] = ob_get_clean();
                break;
        } // switch on action;

        // We always do this
        echo xoutput_from_array($results);
    }
}
