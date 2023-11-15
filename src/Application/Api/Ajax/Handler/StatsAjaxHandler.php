<?php

declare(strict_types=0);

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\System\Core;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Plugin;
use Ampache\Module\System\Session;
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

final class StatsAjaxHandler implements AjaxHandlerInterface
{
    private RequestParserInterface $requestParser;

    public function __construct(
        RequestParserInterface $requestParser
    ) {
        $this->requestParser   = $requestParser;
    }

    public function handle(): void
    {
        $results = array();
        $action  = $this->requestParser->getFromRequest('action');
        /** @var User $user */
        $user = (!empty(Core::get_global('user')))
            ? Core::get_global('user')
            : new User(-1);

        // Switch on the actions
        switch ($action) {
            case 'geolocation':
                if (AmpConfig::get('geolocation')) {
                    if ($user->id > 0) {
                        $name = $_REQUEST['name'] ?? null;
                        if (empty($name)) {
                            $latitude  = (float)($_REQUEST['latitude'] ?? 0);
                            $longitude = (float)($_REQUEST['longitude'] ?? 0);
                            // First try to get from local cache (avoid external api requests)
                            $name = Stats::get_cached_place_name($latitude, $longitude);
                            if (empty($name)) {
                                foreach (Plugin::get_plugins('get_location_name') as $plugin_name) {
                                    $plugin = new Plugin($plugin_name);
                                    if ($plugin->load($user)) {
                                        $name = $plugin->_plugin->get_location_name($latitude, $longitude);
                                        if (!empty($name)) {
                                            break;
                                        }
                                    }
                                }
                            }
                            // Better to check for bugged values here and keep previous user good location
                            // Someone listing music at 0.0,0.0 location would need a waterproof music player btw
                            if ($latitude > 0 && $longitude > 0) {
                                Session::update_geolocation(session_id(), $latitude, $longitude, $name);
                            }
                        }
                } else {
                    debug_event('stats.ajax', 'Geolocation not enabled for the user.', 3);
                }
                break;
            case 'delete_play':
                Stats::delete((int)$_REQUEST['activity_id']);
                ob_start();
                show_now_playing();
                $results['now_playing'] = ob_get_clean();
                ob_start();
                $user_id   = $user->id;
                $data      = Stats::get_recently_played($user_id, 'stream', 'song');
                $ajax_page = 'stats';
                Song::build_cache(array_keys($data));
                require_once Ui::find_template('show_recently_played.inc.php');
                $results['recently_played'] = ob_get_clean();
                break;
            case 'delete_skip':
                Stats::delete((int)$_REQUEST['activity_id']);
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
