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

namespace Ampache\Gui\Playback;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Song;

/**
 * What an action answers a frame with, shared by every action a scraper can point at.
 *
 * Relies on the host class having a `ModelFactoryInterface $modelFactory` property.
 */
trait MediaEmbedTrait
{
    /**
     * Capped: the frame is a taster on someone else's page, not a way to walk a whole library, and every
     * row is a stream url the page carries whether or not anyone clicks it.
     *
     * @param int[] $songIds in the order they should play
     * @return list<Song>
     */
    private function embeddedSongs(array $songIds): array
    {
        $songs = [];
        foreach (array_slice($songIds, 0, MediaEmbedView::EMBED_TRACK_LIMIT) as $songId) {
            $songs[] = $this->modelFactory->createSong($songId);
        }

        return $songs;
    }

    /**
     * What a frame gets when there is no player to put in it, which a stale card keeps asking for.
     *
     * The page itself is never an answer here: a 420 pixel frame would then hold the whole site.
     */
    private function embedUnavailable(bool $visible, string $pageUrl): string
    {
        http_response_code(MediaEmbedUnavailableView::statusFor($visible));

        return (new MediaEmbedUnavailableView(
            ($visible) ? $pageUrl : (string) AmpConfig::get_web_path()
        ))->render();
    }
}
