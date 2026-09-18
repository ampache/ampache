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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /Sessions/Playing/Stopped — clears `now_playing` and decides whether this was a real play, gating
 * on `skip_timer` against the real reported position rather than `has_played_history()`'s own inference.
 */
final class PlayingStoppedMethod implements JellyfinMethodInterface
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

        Stream::delete_now_playing((string) $user->username, $song->id, 'song', $user->id);

        $body          = PlaybackReportHelper::decodeBody($request);
        $positionTicks = (int) (JellyfinRequestBody::field($body, 'PositionTicks') ?? 0);
        $position      = PlaybackReportHelper::ticksToClampedSeconds($positionTicks, $song->time);
        $failed        = (bool) (JellyfinRequestBody::field($body, 'Failed') ?? false);

        if (!$failed && $position >= AmpConfig::get_skip_timer($song->time)) {
            $playedAt = time() - $position;
            if ($song->set_played($user->id, 'Jellyfin', [], $playedAt)) {
                User::save_mediaplay($user, $song);
            }
        }

        // near the end counts as finished: resume position 0 rather than a few seconds from the end
        $resumePosition = ($position >= $song->time - 5) ? 0 : $position;
        Bookmark::create(
            ['comment' => null, 'object_type' => 'song', 'object_id' => $song->id, 'position' => $resumePosition],
            $user->id,
            time(),
        );

        return JellyfinResponse::noContent();
    }
}
