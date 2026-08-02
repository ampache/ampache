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

namespace Ampache\Module\Playback;

use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;

/**
 * Turns a station url that names an m3u or pls playlist into the stream url inside it.
 *
 * Radio directories hand out the playlist rather than the stream, and streaming the playlist itself just sends the
 * client a few lines of text where it expected audio.
 */
final readonly class PlaylistUrlResolver implements PlaylistUrlResolverInterface
{
    /** A playlist naming a station is a few hundred bytes; anything larger is not one. */
    private const int MAX_PLAYLIST_BYTES = 65536;

    /** @var list<string> */
    private const array PLAYLIST_EXTENSIONS = ['m3u', 'm3u8', 'pls', 'asx', 'xspf'];

    /** @var list<string> */
    private const array PLAYLIST_MIMES = [
        'audio/x-scpls',
        'audio/scpls',
        'audio/x-mpegurl',
        'audio/mpegurl',
        'application/pls+xml',
        'application/vnd.apple.mpegurl',
        'application/xspf+xml',
    ];

    private const int TIMEOUT_SECONDS = 5;

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function resolve(string $url): string
    {
        if (!$this->looksLikePlaylist($url)) {
            return $url;
        }

        $body = $this->read($url);
        if ($body === null) {
            return $url;
        }

        $stream = $this->firstStreamUrl($body);
        if ($stream === null) {
            $this->logger->warning(
                'No stream url found inside playlist ' . $url,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            return $url;
        }

        $this->logger->debug(
            'Resolved playlist ' . $url . ' to ' . $stream,
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        return $stream;
    }

    private function contentType(string $url): string
    {
        if (!function_exists('curl_version')) {
            return '';
        }

        $curl = curl_init($url);
        if (!$curl) {
            return '';
        }

        curl_setopt_array($curl, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
        ]);
        curl_exec($curl);

        $type = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

        return strtolower(trim(explode(';', $type)[0]));
    }

    /**
     * Reads both shapes: pls names its entries `File1=`, m3u lists bare urls with `#` comments between them.
     */
    private function firstStreamUrl(string $body): ?string
    {
        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^File\d*\s*=\s*(\S+)/i', $line, $matches) === 1) {
                $line = $matches[1];
            }

            if (preg_match('~^(https?|mms)://~i', $line) === 1) {
                return $line;
            }
        }

        return null;
    }

    /**
     * An extension is the cheap test; a content type catches the directories that serve a playlist from a bare url.
     */
    private function looksLikePlaylist(string $url): bool
    {
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (in_array($extension, self::PLAYLIST_EXTENSIONS, true)) {
            return true;
        }

        // an audio stream answers HEAD with its own type, so this only costs a request for the ambiguous cases
        if ($extension !== '' && $extension !== 'php') {
            return false;
        }

        return in_array($this->contentType($url), self::PLAYLIST_MIMES, true);
    }

    private function read(string $url): ?string
    {
        if (!function_exists('curl_version')) {
            return null;
        }

        $curl = curl_init($url);
        if (!$curl) {
            return null;
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_RANGE => '0-' . (self::MAX_PLAYLIST_BYTES - 1),
        ]);

        $body = curl_exec($curl);

        return (is_string($body) && $body !== '')
            ? $body
            : null;
    }
}
