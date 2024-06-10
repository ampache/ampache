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

namespace Ampache\Application\Api\Upnp;

use Ampache\Application\ApplicationInterface;
use Ampache\Module\Playback\Localplay\Upnp\AmpacheUPnP;
use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Localplay\Upnp\UPnPPlayer;

final class PlayStatusApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!AmpConfig::get('upnp_backend')) {
            die("UPnP backend disabled..");
        }

        // get current UPnP player instance
        $controller = new AmpacheUPnP();
        $instance   = $controller->get_instance();
        echo "UPnP instance = " . $instance['name'] . "\n";

        $deviceDescr = $instance['url'];
        //!!echo "UPnP device = " . $deviceDescr . "\n";
        $player = new UPnPPlayer("background controller", $deviceDescr);

        //!!echo "Current playlist: \n" . print_r($player->GetPlaylistItems(), true);
        //!!echo "Current item: \n" . print_r($player->GetCurrentItem(), true);

        // periodically (every second) checking state of renderer, until it is STOPPED
        $played = false;
        while (($state = $player->GetState()) == "PLAYING") {
            $played = true;
            echo ".";
            sleep(1);
        }
        echo "STATE = " . $state . "\n";

        // If the song was played and then finished, start to play next song in list.
        // Do not start anything if playback was stopped from beginning
        if ($played) {
            echo T_("Play next") . "\n";
            if ($player->Next(false)) {
                echo T_("The next song has started") . "\n";
            } else {
                echo T_("The next song failed to start") . "\n";
            }
        }
    }
}
