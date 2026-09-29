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
use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\JellyfinTranscodeDecision;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\NetworkCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Audio/{itemId}/stream, /universal, and /Items/{itemId}/File (libmpv/media_kit-backed clients fetch
 * media by that path directly). Transcoding only runs when the request itself asks for a specific
 * container/bitrate (`Container`, `AudioCodec`, `AudioBitRate` or `MaxStreamingBitrate`) — a bare request,
 * exactly what every client sent before this existed, always stays direct play.
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
        if (!AmpConfig::get_bool('allow_stream_playback') || !$user->getPreferenceValue('allow_stream_playback')) {
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
        if (
            $song->isNew()
            || !$song->enabled
            || $song->file === null
            || !is_readable($song->file)
            || !Catalog::has_access($song->getCatalogId(), $user->getId())
        ) {
            http_response_code(404);

            return JellyfinResponse::alreadySent();
        }

        // a quota plugin caps what a user may consume, and Legalize mode refuses a media already playing
        if (
            !User::stream_control([['object_type' => 'song', 'object_id' => $song->id]], $user)
            || (AmpConfig::get_bool('lock_songs') && !Stream::check_lock_media($song->id, 'song'))
        ) {
            http_response_code(403);

            return JellyfinResponse::alreadySent();
        }

        $query               = $request->getQueryParams();
        $rawContainer        = JellyfinRequestBody::field($query, 'Container');
        $rawAudioCodec       = JellyfinRequestBody::field($query, 'AudioCodec');
        $rawAudioBitRate     = JellyfinRequestBody::field($query, 'AudioBitRate');
        $rawMaxStreamingRate = JellyfinRequestBody::field($query, 'MaxStreamingBitrate');
        $hasTranscodeHint    = $rawContainer !== null || $rawAudioCodec !== null || $rawAudioBitRate !== null || $rawMaxStreamingRate !== null;
        $isStatic            = in_array(strtolower((string) (JellyfinRequestBody::field($query, 'Static') ?? '')), ['1', 'true'], true);

        if ($hasTranscodeHint && !$isStatic) {
            $decision = JellyfinTranscodeDecision::resolve(
                $song,
                (string) ($rawContainer ?? $rawAudioCodec ?? ''),
                (int) ($rawAudioBitRate ?? 0),
                (int) ($rawMaxStreamingRate ?? 0)
            );

            if ($decision->transcode && $decision->format !== null) {
                return $this->streamTranscoded($request, $song, $decision->format, $query);
            }
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
        header('Content-Length: ' . $length);

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

    /**
     * Runs a transcode and streams it out. No Content-Length is ever declared here — CLAUDE.md's own
     * play-urls notes measured that guess wrong by up to 13% depending on codec, clipping real audio.
     *
     * @param array<string, mixed> $query
     */
    private function streamTranscoded(ServerRequestInterface $request, Song $song, string $format, array $query): JellyfinResponse
    {
        $troptions       = [];
        $rawAudioBitRate = JellyfinRequestBody::field($query, 'AudioBitRate');
        $rawMaxRate      = JellyfinRequestBody::field($query, 'MaxStreamingBitrate');
        $rawStartTicks   = JellyfinRequestBody::field($query, 'StartTimeTicks');

        if ($rawAudioBitRate !== null) {
            $troptions['bitrate'] = (int) $rawAudioBitRate;
        }
        if ($rawMaxRate !== null) {
            $troptions['maxbitrate'] = (int) $rawMaxRate;
        }
        if ($rawStartTicks !== null) {
            // ticks are 100ns units; ffmpeg's %TIME% placeholder wants whole seconds
            $troptions['frame'] = ((int) $rawStartTicks) / 10_000_000;
        } elseif ($song->time > 0) {
            $troptions['duration'] = (float) $song->time;
        }

        $transcodeSettings = $song->get_transcode_settings($format, 'jellyfin', $troptions);
        $transcoder        = Stream::start_transcode($song, $transcodeSettings, $troptions, 'jellyfin');
        $handle            = $transcoder['handle'] ?? null;

        if (!is_resource($handle)) {
            http_response_code(500);

            return JellyfinResponse::alreadySent();
        }

        header('Content-Type: ' . Song::type_to_mime($transcoder['format'] ?? $format));
        header('Accept-Ranges: none');
        http_response_code(200);

        if (strtoupper($request->getMethod()) === 'HEAD') {
            fclose($handle);
            Stream::kill_process($transcoder);

            return JellyfinResponse::alreadySent();
        }

        $bytesStreamed = 0;
        do {
            $chunk = fread($handle, self::CHUNK_SIZE);
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            flush();
            $bytesStreamed += strlen($chunk);
        } while (
            connection_status() === 0
            && (!feof($handle) || (!empty($transcoder['process']) && is_resource($transcoder['process']) && proc_get_status($transcoder['process'])['running']))
        );

        fclose($handle);
        Stream::kill_process($transcoder);

        if ($bytesStreamed === 0) {
            // headers are already sent, so this can't recover to a clean error status — same as a missing source file above
            http_response_code(500);
        }

        return JellyfinResponse::alreadySent();
    }
}
