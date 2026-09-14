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

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Audio/{itemId}/stream, /universal, and /Items/{itemId}/File (libmpv/media_kit-backed clients fetch
 * media by that path directly) — all direct-play only in v1, all served by the same range/HEAD byte-stream.
 */
final class AudioStreamMethod implements JellyfinMethodInterface
{
    private const int CHUNK_SIZE = 8192;

    public function __construct(private readonly NetworkCheckerInterface $networkChecker) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            http_response_code(401);

            return JellyfinResponse::alreadySent();
        }

        // same gates PlayAction applies to the equivalent native byte-serving path
        if (!AmpConfig::get_bool('allow_stream_playback') || !(bool) $user->getPreferenceValue('allow_stream_playback')) {
            http_response_code(403);

            return JellyfinResponse::alreadySent();
        }
        if (
            AmpConfig::get_bool('access_control')
            && !$this->networkChecker->check(AccessTypeEnum::STREAM, $user->getId())
            && !$this->networkChecker->check(AccessTypeEnum::NETWORK, $user->getId())
        ) {
            http_response_code(403);

            return JellyfinResponse::alreadySent();
        }

        $itemId = (string) ($request->getAttribute('itemId') ?? '');
        if (!JellyfinId::isType($itemId, 'song')) {
            http_response_code(404);

            return JellyfinResponse::alreadySent();
        }

        $song = new Song((int) JellyfinId::decodeId($itemId));
        if ($song->isNew() || !$song->enabled || $song->file === null || !is_readable($song->file)) {
            http_response_code(404);

            return JellyfinResponse::alreadySent();
        }

        $fileSize = ($song->size > 0) ? $song->size : (int) filesize($song->file);
        $mime     = $song->mime ?: Song::type_to_mime($song->type);

        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');

        [$start, $end, $isRange, $satisfiable] = $this->resolveRange($request->getHeaderLine('Range'), $fileSize);
        if (!$satisfiable) {
            header('HTTP/1.1 416 Range Not Satisfiable');
            header('Content-Range: bytes */' . $fileSize);

            return JellyfinResponse::alreadySent();
        }

        $length = $end - $start + 1;
        if ($isRange) {
            http_response_code(206);
            header(sprintf('Content-Range: bytes %d-%d/%d', $start, $end, $fileSize));
        } else {
            http_response_code(200);
        }
        header('Content-Length: ' . (string) $length);

        if (strtoupper($request->getMethod()) === 'HEAD') {
            return JellyfinResponse::alreadySent();
        }

        $handle = fopen($song->file, 'rb');
        if ($handle === false) {
            // headers are already sent, so this can't recover to a clean error status — same as PlayAction
            return JellyfinResponse::alreadySent();
        }

        fseek($handle, $start);
        $remaining = $length;
        while ($remaining > 0 && !feof($handle) && connection_status() === 0) {
            $chunk = fread($handle, min(self::CHUNK_SIZE, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            flush();
            $remaining -= strlen($chunk);
        }
        fclose($handle);

        return JellyfinResponse::alreadySent();
    }

    /** @return array{0: int, 1: int, 2: bool, 3: bool} start, end, isRange, satisfiable */
    private function resolveRange(string $rangeHeader, int $fileSize): array
    {
        if ($rangeHeader === '' || preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches) !== 1) {
            return [0, max(0, $fileSize - 1), false, true];
        }

        $hasStart = $matches[1] !== '';
        $hasEnd   = $matches[2] !== '';

        if (!$hasStart && $hasEnd) {
            // a suffix range asks for the last N bytes of the file
            $suffixLength = (int) $matches[2];
            $start        = max(0, $fileSize - $suffixLength);
            $end          = $fileSize - 1;
        } else {
            $start = $hasStart ? (int) $matches[1] : 0;
            $end   = $hasEnd ? min((int) $matches[2], $fileSize - 1) : $fileSize - 1;
        }

        if ($start > $end || $start >= $fileSize || $fileSize <= 0) {
            return [0, 0, true, false];
        }

        return [$start, $end, true, true];
    }
}
