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

use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UrlValidatorInterface;
use CurlHandle;
use Psr\Log\LoggerInterface;

/**
 * Streams a remote url back to the client instead of redirecting to it.
 *
 * The browser only ever sees this server, so a preview or a radio station is same-origin like any local song. That
 * matters because a `MediaElementSourceNode` cannot be undone once created: a cross-origin resource reaching the
 * web player's audio graph afterwards is silenced, which is what a mixed queue of local, radio and preview items hits.
 */
final readonly class StreamProxy implements StreamProxyInterface
{
    /** @var int How many redirects a proxied stream will follow, each one checked before it is connected to */
    private const int MAX_REDIRECTS = 10;

    public function __construct(
        private LoggerInterface $logger,
        private UrlValidatorInterface $urlValidator,
    ) {}

    public function proxy(string $url): bool
    {
        if (!function_exists('curl_version')) {
            return false;
        }

        // the url comes from a stored live_stream/remote row, so it is refetched from the network on every
        // play; each hop is followed by hand below rather than left to curl, so its own address can be
        // validated and pinned before connecting instead of curl re-resolving the hostname unchecked
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $target = $this->urlValidator->resolvePinnedTarget($url);
            if ($target === null) {
                $this->logger->warning(
                    'Stream proxy refusing url: ' . $url,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                return false;
            }

            $curl = curl_init($url);
            if (!$curl) {
                return false;
            }

            $this->logger->debug(
                'Stream proxy: ' . $url,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );

            // a mutable holder rather than a by-reference closure capture, so the write callback's read of
            // a value the header callback assigns later is typed by its declared property, not narrowed to
            // the literal null it holds at the point the closures are defined
            $redirect = new StreamRedirect();

            curl_setopt_array(
                $curl,
                [
                    CURLOPT_FAILONERROR => true,
                    CURLOPT_HTTPHEADER => $this->getRequestHeaders(),
                    CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => false,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $target['host'], $target['port'], $target['address'])],
                    CURLOPT_WRITEFUNCTION => function (CurlHandle $curl, string $data) use ($redirect): int {
                        unset($curl);

                        // a redirect response's own body, if it has one, is never the media the client asked for
                        if ($redirect->location !== null) {
                            return strlen($data);
                        }

                        echo $data;
                        ob_flush();
                        flush();

                        return strlen($data);
                    },
                    CURLOPT_HEADERFUNCTION => function (CurlHandle $curl, string $header) use ($redirect): int {
                        return $this->captureHeader($curl, $header, $redirect);
                    },
                    // Default trusted chain is crap anyway and currently no custom CA option
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    // a radio station never ends, so the transfer must not time out
                    CURLOPT_TIMEOUT => 0,
                ]
            );

            $success = curl_exec($curl) !== false;
            $error   = curl_error($curl);
            curl_close($curl);

            if ($redirect->location !== null) {
                $url = $redirect->location;

                continue;
            }

            if (!$success) {
                $this->logger->error(
                    'Stream proxy error: ' . $error,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }

            return $success;
        }

        $this->logger->warning(
            'Stream proxy: too many redirects fetching ' . $url,
            [LegacyLogger::CONTEXT_TYPE => self::class]
        );

        return false;
    }

    /**
     * Reads one response header line; a Location is recorded rather than followed, so the caller can validate
     * and pin the next hop itself instead of curl reconnecting to a hostname nobody re-checked
     */
    private function captureHeader(CurlHandle $curl, string $header, StreamRedirect $redirect): int
    {
        $rheader = trim($header);
        $rhpart  = explode(':', $rheader);
        // the status line carries no colon, and a range request has to keep its 206 rather than fall back to 200
        if (preg_match('~^HTTP/[\d.]+\s+(\d{3})~', $rheader, $matches) === 1) {
            http_response_code((int) $matches[1]);

            return strlen($header);
        }

        if (strcasecmp($rhpart[0], 'Location') === 0 && count($rhpart) > 1) {
            $redirect->location = $this->resolveRedirectLocation($curl, trim(substr($rheader, strlen($rhpart[0]) + 1)));

            return strlen($header);
        }

        // this server decides the transfer encoding, so passing the remote one on would corrupt the response
        if ($rheader !== '' && count($rhpart) > 1 && $rhpart[0] !== 'Transfer-Encoding') {
            header($rheader);
        }

        return strlen($header);
    }

    /**
     * Carries the client's range request through, so seeking still works on the proxied stream.
     *
     * @return list<string>
     */
    private function getRequestHeaders(): array
    {
        $headers    = (function_exists('apache_request_headers')) ? apache_request_headers() : [];
        $reqheaders = [];
        if (!empty($headers['User-Agent'])) {
            $reqheaders[] = 'User-Agent: ' . $headers['User-Agent'];
        }

        if (!empty($headers['Range'])) {
            $reqheaders[] = 'Range: ' . $headers['Range'];
        }

        $reqheaders[] = 'X-Forwarded-For: ' . Core::get_user_ip();

        return $reqheaders;
    }

    /**
     * The absolute url a Location header points at, resolving a relative one against the url that answered it
     */
    private function resolveRedirectLocation(CurlHandle $curl, string $location): string
    {
        if ($location === '' || parse_url($location, PHP_URL_SCHEME) !== null) {
            return $location;
        }

        $base = parse_url(curl_getinfo($curl, CURLINFO_EFFECTIVE_URL));

        return sprintf(
            '%s://%s%s%s',
            $base['scheme'] ?? 'http',
            $base['host'] ?? '',
            isset($base['port']) ? ':' . $base['port'] : '',
            str_starts_with($location, '/') ? $location : '/' . $location
        );
    }
}
