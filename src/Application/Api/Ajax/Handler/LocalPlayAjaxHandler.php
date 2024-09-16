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

use Ampache\Module\Authorization\Access;
use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Repository\Model\Preference;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\User;

final readonly class LocalPlayAjaxHandler implements AjaxHandlerInterface
{
    public function __construct(
        private RequestParserInterface $requestParser
    ) {
    }

    public function handle(User $user): void
    {
        $results = [];
        $action  = $this->requestParser->getFromRequest('action');

        // Switch on the actions
        switch ($action) {
            case 'set_instance':
                // Make sure they are allowed to do this
                if (!Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::GUEST)) {
                    debug_event('localplay.ajax', 'Error attempted to set instance without required level', 1);

                    return;
                }

                $type = (isset($_REQUEST['instance'])) ? 'localplay' : 'stream';

                $localplay = new LocalPlay(AmpConfig::get('localplay_controller', ''));
                $localplay->set_active_instance((int)$_REQUEST['instance']);
                Preference::update('play_type', $user->getId(), $type);

                // We should also refresh the sidebar
                ob_start();
                require_once Ui::find_template('sidebar.inc.php');
                $results['sidebar-content'] = ob_get_contents();
                ob_end_clean();
                break;
            case 'command':
                // Make sure they are allowed to do this
                if (!Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::from((int) AmpConfig::get('localplay_level', AccessLevelEnum::ADMIN->value)))) {
                    debug_event('localplay.ajax', 'Attempted to control Localplay without sufficient access', 1);

                    return;
                }

                $localplay = new LocalPlay(AmpConfig::get('localplay_controller', ''));
                $localplay->connect();

                // Switch on valid commands
                switch ($_REQUEST['command']) {
                    case 'refresh':
                        ob_start();
                        $objects = $localplay->get();
                        require_once Ui::find_template('show_localplay_status.inc.php');
                        $results['localplay_status'] = ob_get_contents();
                        ob_end_clean();
                        break;
                    case 'prev':
                        $localplay->prev();
                        break;
                    case 'next':
                        $localplay->next();
                        break;
                    case 'stop':
                        $localplay->stop();
                        break;
                    case 'play':
                        $localplay->play();
                        break;
                    case 'pause':
                        $command = scrub_in((string) $_REQUEST['command']);
                        $localplay->$command();
                        break;
                    case 'volume_up':
                        $localplay->volume_up();
                        break;
                    case 'volume_down':
                        $localplay->volume_down();
                        break;
                    case 'volume_mute':
                        $command = scrub_in((string) $_REQUEST['command']);
                        $localplay->$command();

                        // We actually want to refresh something here
                        ob_start();
                        $objects = $localplay->get();
                        require_once Ui::find_template('show_localplay_status.inc.php');
                        $results['localplay_status'] = ob_get_contents();
                        ob_end_clean();
                        break;
                    case 'delete_all':
                        $localplay->delete_all();
                        ob_start();
                        $browse = new Browse();
                        $browse->set_type('playlist_localplay');
                        $browse->set_static_content(true);
                        $browse->save_objects([]);
                        $browse->show_objects();
                        $browse->store();
                        $results[$browse->get_content_div()] = ob_get_contents();
                        ob_end_clean();
                        break;
                    case 'skip':
                        $localplay->skip((int) filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT));
                        $objects = $localplay->get();
                        ob_start();
                        $browse = new Browse();
                        $browse->set_type('playlist_localplay');
                        $browse->set_static_content(true);
                        $browse->save_objects($objects);
                        $browse->show_objects($objects);
                        $browse->store();
                        $results[$browse->get_content_div()] = ob_get_contents();
                        ob_end_clean();
                        break;
                } // end whitelist

                break;
            case 'delete_track':
                if (!Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::from((int) AmpConfig::get('localplay_level', AccessLevelEnum::ADMIN->value)))) {
                    debug_event('localplay.ajax', 'Attempted to delete track without access', 1);

                    return;
                }

                $localplay = new LocalPlay(AmpConfig::get('localplay_controller', ''));
                $localplay->connect();

                // Scrub in the delete request
                $id = (int) filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

                $localplay->delete_track($id);

                // Wait in case we just deleted what we were playing
                sleep(3);
                $objects = $localplay->get();

                ob_start();
                $browse_id = (int)($_REQUEST['browse_id'] ?? 0);
                $browse    = new Browse($browse_id);
                $browse->set_type('playlist_localplay');
                $browse->set_static_content(true);
                $browse->save_objects($objects);
                $browse->show_objects($objects);
                $browse->store();
                $results[$browse->get_content_div()] = ob_get_contents();
                ob_end_clean();

                break;
            case 'delete_instance':
                // Make sure that you have access to do this...
                if (!Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::MANAGER)) {
                    debug_event('localplay.ajax', 'Attempted to delete instance without access', 1);

                    return;
                }

                // Scrub it in
                $localplay = new LocalPlay(AmpConfig::get('localplay_controller', ''));
                $localplay->delete_instance((int)$_REQUEST['instance']);

                $key           = 'localplay_instance_' . $_REQUEST['instance'];
                $results[$key] = '';
                break;
            case 'repeat':
                // Make sure that they have access to do this again no clue
                if (!Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::from((int) AmpConfig::get('localplay_level', AccessLevelEnum::ADMIN->value)))) {
                    debug_event('localplay.ajax', 'Attempted to set repeat without access', 1);

                    return;
                }

                // Scrub her in
                $localplay = new LocalPlay(AmpConfig::get('localplay_controller', ''));
                $localplay->connect();
                $localplay->repeat(make_bool($_REQUEST['value']));

                ob_start();
                $objects = $localplay->get();
                require_once Ui::find_template('show_localplay_status.inc.php');
                $results['localplay_status'] = ob_get_contents();
                ob_end_clean();

                break;
            case 'random':
                // Make sure that they have access to do this
                if (!Access::check(AccessTypeEnum::LOCALPLAY, AccessLevelEnum::from((int) AmpConfig::get('localplay_level', AccessLevelEnum::ADMIN->value)))) {
                    debug_event('localplay.ajax', 'Attempted to set random without access', 1);

                    return;
                }

                // Scrub her in
                $localplay = new LocalPlay(AmpConfig::get('localplay_controller', ''));
                $localplay->connect();
                $localplay->random(make_bool($_REQUEST['value']));

                ob_start();
                $objects = $localplay->get();
                require_once Ui::find_template('show_localplay_status.inc.php');
                $results['localplay_status'] = ob_get_contents();
                ob_end_clean();
        } // switch on action;

        // We always do this
        echo (string) xoutput_from_array($results);
    }
}
