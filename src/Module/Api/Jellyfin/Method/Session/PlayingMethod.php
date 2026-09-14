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

use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /Sessions/Playing — playback start. Only mirrors into `now_playing`; the play-count/scrobble
 * decision waits for the real position data `Sessions/Playing/Stopped` reports (see that handler).
 */
final class PlayingMethod implements JellyfinMethodInterface
{
    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $song = PlaybackReportHelper::resolveSong($request);
        if ($song === null) {
            return JellyfinResponse::noContent();
        }

        $body          = PlaybackReportHelper::decodeBody($request);
        $positionTicks = (int) (JellyfinRequestBody::field($body, 'PositionTicks') ?? 0);
        $position      = PlaybackReportHelper::ticksToClampedSeconds($positionTicks, $song->time);
        $state         = ((bool) (JellyfinRequestBody::field($body, 'IsPaused') ?? false)) ? 'paused' : 'playing';

        Stream::garbage_collection();
        Stream::insert_now_playing(
            $song->id,
            $user->id,
            $song->time,
            (string) $user->username,
            'song',
            time() - $position,
            $position * 1000,
            1.0,
            $state,
        );

        return JellyfinResponse::noContent();
    }
}
