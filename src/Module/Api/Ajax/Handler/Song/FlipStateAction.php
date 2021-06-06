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

namespace Ampache\Module\Api\Ajax\Handler\Song;

use Ampache\Module\Api\Ajax;
use Ampache\Module\Api\Ajax\Handler\ActionInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FlipStateAction implements ActionInterface
{
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        User $user
    ): array {
        if (!Access::check('interface', 75)) {
            debug_event('song.ajax', Core::get_global('user')->username . ' attempted to change the state of a song', 1);

            return [];
        }

        $song        = new Song($_REQUEST['song_id']);
        $new_enabled = $song->isEnabled() ? false : true;
        Song::update_enabled($new_enabled, $song->id);
        $song->format();

        // Return the new Ajax::button
        $id           = 'button_flip_state_' . $song->id;
        if ($new_enabled) {
            $button     = 'disable';
            $buttontext = T_('Disable');
        } else {
            $button     = 'enable';
            $buttontext = T_('Enable');
        }
        $results[$id] = Ajax::button('?page=song&action=flip_state&song_id=' . $song->id, $button, $buttontext, 'flip_state_' . $song->id);

        return $results;
    }
}
