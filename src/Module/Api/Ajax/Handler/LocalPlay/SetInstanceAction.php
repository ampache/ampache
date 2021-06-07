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
 */

declare(strict_types=0);

namespace Ampache\Module\Api\Ajax\Handler\LocalPlay;

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SetInstanceAction implements ActionInterface
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];

        // Make sure they they are allowed to do this
        if (!Access::check('localplay', 5)) {
            debug_event('localplay.ajax', 'Error attempted to set instance without required level', 1);

            return $results;
        }

        $type = $_REQUEST['instance'] ? 'localplay' : 'stream';

        $localplay = new LocalPlay(AmpConfig::get('localplay_controller'));
        $localplay->set_active_instance($_REQUEST['instance']);
        Preference::update('play_type', Core::get_global('user')->id, $type);

        // We should also refresh the sidebar
        ob_start();
        require_once Ui::find_template('sidebar.inc.php');
        $results['sidebar-content'] = ob_get_contents();
        ob_end_clean();

        return $results;
    }
}
