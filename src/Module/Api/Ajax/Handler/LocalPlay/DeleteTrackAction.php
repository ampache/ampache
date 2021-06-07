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
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DeleteTrackAction implements ActionInterface
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        $results = [];

        if (!Access::check('localplay', 50)) {
            debug_event('localplay.ajax', 'Attempted to delete track without access', 1);

            return $results;
        }
        $localplay = new LocalPlay(AmpConfig::get('localplay_controller'));
        $localplay->connect();

        // Scrub in the delete request
        $id = (int) filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

        $localplay->delete_track($id);

        // Wait in case we just deleted what we were playing
        sleep(3);
        $objects = $localplay->get();
        $status  = $localplay->status();

        ob_start();
        $browse = new Browse();
        $browse->set_type('playlist_localplay');
        $browse->set_static_content(true);
        $browse->save_objects($objects);
        $browse->show_objects($objects);
        $browse->store();
        $results[$browse->get_content_div()] = ob_get_contents();
        ob_end_clean();

        return $results;
    }
}
