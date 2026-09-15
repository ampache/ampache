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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\Song;

/**
 * Whether a song needs transcoding for this surface, and to which format. `PlaybackInfoMethod` advertises
 * this up front and `AudioStreamMethod` has to reach the same answer to actually run it, so both go through
 * here rather than risk offering one thing and serving another. `'jellyfin'` is used as the player identity
 * throughout, so this reads `encode_player_jellyfin_target` rather than falling into the webplayer default.
 *
 * A real client (confirmed with `jellyfin_apiclient_python`, the library behind several real Jellyfin
 * clients) sends `MaxStreamingBitrate` on every request by default — usually a huge, effectively "no cap"
 * value. Treating any configured `encode_target` as reason enough to transcode, the way an earlier version
 * of this class did, meant every such request got force-transcoded on a server that has `encode_target` set
 * for an unrelated reason (e.g. normalising the web player), even though the client never asked for a
 * specific format and its bitrate cap was nowhere near the source rate. Whether to transcode at all is
 * decided independently of what format to transcode to, mirroring `PlayAction`'s own two-step shape.
 */
final class JellyfinTranscodeDecision
{
    private function __construct(
        public readonly bool $transcode,
        public readonly ?string $format,
    ) {}

    /**
     * @param string $requestedContainer an explicit container/codec ask, or '' if the client didn't name one
     * @param int $requestedBitrate an explicit bps ask (e.g. AudioBitRate), 0 if none
     * @param int $maxBitrate a bps ceiling (e.g. MaxStreamingBitrate), 0 if none
     */
    public static function resolve(Song $song, string $requestedContainer, int $requestedBitrate, int $maxBitrate): self
    {
        $cap = ($requestedBitrate > 0) ? $requestedBitrate : $maxBitrate;

        $shouldTranscode = $requestedContainer !== ''
            || AmpConfig::get('transcode', 'default') === 'always'
            || ($cap > 0 && $cap < $song->bitrate);

        if (!$shouldTranscode) {
            return new self(false, null);
        }

        $target = Stream::get_transcode_format($song->type, ($requestedContainer !== '') ? $requestedContainer : null, 'jellyfin');
        if (!$target) {
            return new self(false, null);
        }

        $skip = Stream::skip_transcode($target, $song->type, (int) $song->bitrate, $requestedBitrate, $maxBitrate, 'jellyfin');

        return new self(!$skip, $target);
    }
}
