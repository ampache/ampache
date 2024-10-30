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

namespace Ampache\Application\Api\Ajax\Handler;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Plugin\PluginRetrieverInterface;
use Ampache\Module\System\Plugin\PluginTypeEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Ui;
use Ampache\Module\System\Session;
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

final readonly class StatsAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser,
        private PluginRetrieverInterface $pluginRetriever
    ) {
    }

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
                            empty($name) &&
                            !empty($_REQUEST['latitude']) &&
                            !empty($_REQUEST['longitude'])
                        ) {
                            $latitude  = (float)$_REQUEST['latitude'];
                            $longitude = (float)$_REQUEST['longitude'];
                            // First try to get from local cache (avoid external api requests)
                            $name = Stats::get_cached_place_name($latitude, $longitude);
                            if (empty($name)) {
                                foreach ($this->pluginRetriever->retrieveByType(PluginTypeEnum::GEO_LOCATION, $user) as $plugin) {
                                    $name = $plugin->_plugin->get_location_name($latitude, $longitude);
                                    if (!empty($name)) {
                                        break;
                                    }
                                }
                            }

                            // Better to check for bugged values here and keep previous user good location
                            // Someone listing music at 0.0,0.0 location would need a waterproof music player btw
                            if (
                                !empty($name) &&
                                $latitude > 0 &&
                                $longitude > 0
                            ) {
                                Session::update_geolocation((string)session_id(), $latitude, $longitude, $name);
                            }
                        }
                    }
                } else {
                    debug_event('stats.ajax', 'Geolocation not enabled for the user.', 3);
                }

                break;
            case 'delete_play':
                if (
                    check_http_referer() === true &&
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) &&
                    isset($_REQUEST['activity_id'])
                ) {
                    Stats::delete((int)$_REQUEST['activity_id']);
                }

                ob_start();
                show_now_playing();
                $results['now_playing'] = ob_get_clean();
                ob_start();
                $user_id   = $user->id;
                $ajax_page = 'stats';
                if (AmpConfig::get('home_recently_played_all')) {
                    $data = Stats::get_recently_played($user_id);
                    require_once Ui::find_template('show_recently_played_all.inc.php');
                } else {
                    $data = Stats::get_recently_played($user_id, 'stream', 'song');
                    Song::build_cache(array_keys($data));
                    require Ui::find_template('show_recently_played.inc.php');
                }

                $results['recently_played'] = ob_get_clean();
                break;
            case 'delete_skip':
                if (
                    check_http_referer() === true &&
                    Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN) &&
                    isset($_REQUEST['activity_id'])
                ) {
                    Stats::delete((int)$_REQUEST['activity_id']);
                }

                ob_start();
                show_now_playing();
                $results['now_playing'] = ob_get_clean();
                ob_start();
                $user_id   = $user->id;
                $data      = Stats::get_recently_played($user_id, 'skip', 'song');
                $ajax_page = 'stats';
                Song::build_cache(array_keys($data));
                require_once Ui::find_template('show_recently_skipped.inc.php');
                $results['recently_skipped'] = ob_get_clean();
                break;
        } // switch on action;

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
