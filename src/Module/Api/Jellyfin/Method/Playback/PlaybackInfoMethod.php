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
use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\JellyfinTranscodeDecision;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET/POST /Items/{itemId}/PlaybackInfo. Advertises a transcode option, decided the same simple way the
 * native API does (source format/bitrate vs. server config), rather than parsing the client's DeviceProfile
 * codec/container support like a real Jellyfin server does. Direct play is always still offered.
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
        if ($song->isNew() || !Catalog::has_access($song->getCatalogId(), $user->getId())) {
            return JellyfinResponse::notFound();
        }

        $body           = json_decode((string) $request->getBody(), true);
        $maxBitrate     = (int) (JellyfinRequestBody::field(is_array($body) ? $body : [], 'MaxStreamingBitrate') ?? 0);
        $decision       = JellyfinTranscodeDecision::resolve($song, '', 0, $maxBitrate);
        $playSessionId  = bin2hex(random_bytes(16));
        $runTimeTicks   = $song->time * 10_000_000;

        $mediaSource = [
            'Id' => $itemId,
            'Protocol' => 'File',
            'Container' => $song->type,
            'Size' => $song->size,
            'Bitrate' => $song->bitrate,
            'RunTimeTicks' => $runTimeTicks,
            'SupportsDirectPlay' => true,
            'SupportsDirectStream' => true,
            'SupportsTranscoding' => $decision->transcode,
            'IsRemote' => false,
            'MediaStreams' => [
                [
                    'Type' => 'Audio',
                    'Codec' => $song->type,
                    'BitRate' => $song->bitrate,
                    'Index' => 0,
                ],
            ],
        ];

        if ($decision->transcode && $decision->format !== null) {
            $query = http_build_query([
                'Container' => $decision->format,
                'AudioCodec' => $decision->format,
                'PlaySessionId' => $playSessionId,
            ]);

            $mediaSource['TranscodingUrl']          = '/Audio/' . $itemId . '/stream?' . $query;
            $mediaSource['TranscodingContainer']    = $decision->format;
            $mediaSource['TranscodingSubProtocol']  = 'http';
        }

        return JellyfinResponse::json([
            'MediaSources' => [$mediaSource],
            'PlaySessionId' => $playSessionId,
        ]);
    }
}
