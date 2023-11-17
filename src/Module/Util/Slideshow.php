<?php

/**
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

declare(strict_types=0);

namespace Ampache\Module\Util;

use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Plugin;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Song;

final class Slideshow implements SlideshowInterface
{
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    public function getCurrentSlideshow(): array
    {
        $user_id = Core::get_global('user')->id ?? -1;
        $songs   = Stats::get_recently_played($user_id, 'stream', 'song');
        $images  = [];
        if ($songs !== []) {
            $last_song = $this->modelFactory->createSong((int) $songs[0]['object_id']);
            $last_song->format();
            $images = $this->getImages($last_song);
        }

        return $images;
    }

    private function getImages(Song $song): array
    {
        $images = [];

        foreach (Plugin::get_plugins('get_photos') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load(Core::get_global('user'))) {
                $images += $plugin->_plugin->get_photos($song->f_artist);
            }
        }

        return $images;
    }
}
