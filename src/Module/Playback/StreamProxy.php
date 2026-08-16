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
    public function __construct(
        private LoggerInterface $logger,
        private UrlValidatorInterface $urlValidator,
    ) {}

    public function proxy(string $url): bool
    {
        if (!function_exists('curl_version')) {
            return false;
        }

        // The url comes from a stored live_stream/remote row, so it is refetched from the network on every
        // play; curl still follows redirects server-side (see outputHeader()), each one checked in turn.
        if (!$this->urlValidator->isPublicHttpUrl($url)) {
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

        curl_setopt_array(
            $curl,
            [
                CURLOPT_FAILONERROR => true,
                CURLOPT_HTTPHEADER => $this->getRequestHeaders(),
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_WRITEFUNCTION => $this->outputBody(...),
                CURLOPT_HEADERFUNCTION => $this->outputHeader(...),
                // Default trusted chain is crap anyway and currently no custom CA option
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                // a radio station never ends, so the transfer must not time out
                CURLOPT_TIMEOUT => 0,
            ]
        );

        $success = curl_exec($curl) !== false;
        if (!$success) {
            $this->logger->error(
                'Stream proxy error: ' . curl_error($curl),
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        return $success;
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

    private function outputBody(CurlHandle $curl, string $data): int
    {
        unset($curl);

        echo $data;
        ob_flush();
        flush();

        return strlen($data);
    }

    private function outputHeader(CurlHandle $curl, string $header): int
    {
        $rheader = trim($header);
        $rhpart  = explode(':', $rheader);
        // the status line carries no colon, and a range request has to keep its 206 rather than fall back to 200
        if (preg_match('~^HTTP/[\d.]+\s+(\d{3})~', $rheader, $matches) === 1) {
            http_response_code((int) $matches[1]);

            return strlen($header);
        }

        // curl follows this redirect itself; refuse the hop rather than let it reach a private address
        if (strcasecmp($rhpart[0], 'Location') === 0 && count($rhpart) > 1) {
            $location = $this->resolveRedirectLocation($curl, trim(substr($rheader, strlen($rhpart[0]) + 1)));
            if (!$this->urlValidator->isPublicHttpUrl($location)) {
                $this->logger->warning(
                    'Stream proxy refusing redirect to: ' . $location,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );

                // any return value other than the header's own length aborts the transfer
                return 0;
            }
        }

        // this server decides the transfer encoding, so passing the remote one on would corrupt the response
        if ($rheader !== '' && count($rhpart) > 1 && $rhpart[0] !== 'Transfer-Encoding') {
            header($rheader);
        }

        return strlen($header);
    }

    /**
     * The absolute url a Location header points at, resolving a relative one against the url that answered it
     */
    private function resolveRedirectLocation(CurlHandle $curl, string $location): string
    {
        if ($location === '' || parse_url($location, PHP_URL_SCHEME) !== null) {
            return $location;
        }

        $base = parse_url((string) curl_getinfo($curl, CURLINFO_EFFECTIVE_URL));

        return sprintf(
            '%s://%s%s%s',
            $base['scheme'] ?? 'http',
            $base['host'] ?? '',
            isset($base['port']) ? ':' . $base['port'] : '',
            str_starts_with($location, '/') ? $location : '/' . $location
        );
    }
}
