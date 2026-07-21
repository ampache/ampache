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

namespace Ampache\Module\Application\MiniPlayer;

use Ampache\Config\AmpConfig;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Show the mini player; a standalone page with the home plugins and the web player only.
 */
final readonly class ShowAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'show';

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $user = Core::get_global('user');

        // Users locked into this page can't reach their preferences to fix a play type that doesn't
        // load the web player, so make sure it is set. Core::get_reloadutil() only returns the
        // in-page loader for the web player play type.
        if (
            $user instanceof User
            && $user->getId() > 0
            && Preference::get_by_user($user->getId(), 'mini_player')
        ) {
            if (AmpConfig::get('play_type') !== 'web_player') {
                Preference::update('play_type', $user->getId(), 'web_player');
                AmpConfig::set('play_type', 'web_player', true);
            }
        }

        require_once Ui::find_template('mini.inc.php');

        return null;
    }
}
