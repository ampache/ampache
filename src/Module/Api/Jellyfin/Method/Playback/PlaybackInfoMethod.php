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

namespace Ampache\Module\Api\Jellyfin\Method\Playback;

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET/POST /Items/{itemId}/PlaybackInfo — direct play only in v1, no transcode decision yet. The client
 * builds its own `/Audio/{itemId}/stream` URL from `Id`; no URL is returned here.
 */
final class PlaybackInfoMethod implements JellyfinMethodInterface
{
    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $itemId = (string) ($request->getAttribute('itemId') ?? '');
        if (!JellyfinId::isType($itemId, 'song')) {
            return JellyfinResponse::notFound();
        }

        $song = new Song((int) JellyfinId::decodeId($itemId));
        if ($song->isNew()) {
            return JellyfinResponse::notFound();
        }

        $runTimeTicks = $song->time * 10_000_000;

        return JellyfinResponse::json([
            'MediaSources' => [
                [
                    'Id' => $itemId,
                    'Protocol' => 'File',
                    'Container' => $song->type,
                    'Size' => $song->size,
                    'Bitrate' => $song->bitrate,
                    'RunTimeTicks' => $runTimeTicks,
                    'SupportsDirectPlay' => true,
                    'SupportsDirectStream' => true,
                    'SupportsTranscoding' => false,
                    'IsRemote' => false,
                    'MediaStreams' => [
                        [
                            'Type' => 'Audio',
                            'Codec' => $song->type,
                            'BitRate' => $song->bitrate,
                            'Index' => 0,
                        ],
                    ],
                ],
            ],
            'PlaySessionId' => bin2hex(random_bytes(16)),
        ]);
    }
}
