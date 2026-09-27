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

namespace Ampache\Module\Api\Jellyfin\Method\Session;

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Shared body-decoding and .NET-tick conversion for the `Sessions/Playing*` family — all three real payload
 * shapes (Start/Progress/Stop) carry the same `ItemId`/`PositionTicks` fields.
 */
final class PlaybackReportHelper
{
    /** A Jellyfin tick is 100ns, so a second is 10 million of them (`System.TimeSpan`'s own unit). */
    public const int TICKS_PER_SECOND = 10_000_000;

    /** @return array<string, mixed> */
    public static function decodeBody(ServerRequestInterface $request): array
    {
        $body = json_decode((string) $request->getBody(), true);

        return is_array($body) ? $body : [];
    }

    /** Only `Audio` items are ever streamed by this surface, so anything else resolves to no song. */
    public static function resolveSong(ServerRequestInterface $request, User $user): ?Song
    {
        $itemId = (string) (JellyfinRequestBody::field(self::decodeBody($request), 'ItemId') ?? '');
        if ($itemId === '' || !JellyfinId::isType($itemId, 'song')) {
            return null;
        }

        $songId = JellyfinId::decodeId($itemId);
        if ($songId === null) {
            return null;
        }

        $song = new Song($songId);

        return ($song->isNew() || !Catalog::has_access($song->getCatalogId(), $user->getId())) ? null : $song;
    }

    /** Clamps to the track's own length so a stray/out-of-range position can't pin a stuck `now_playing` row. */
    public static function ticksToClampedSeconds(int $positionTicks, int $songTime): int
    {
        return max(0, min(intdiv(max(0, $positionTicks), self::TICKS_PER_SECOND), $songTime));
    }
}
