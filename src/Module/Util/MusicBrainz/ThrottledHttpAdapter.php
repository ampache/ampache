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

namespace Ampache\Module\Util\MusicBrainz;

use MusicBrainz\Exception;
use MusicBrainz\HttpAdapters\AbstractHttpAdapter;
use WpOrg\Requests\Requests;
use WpOrg\Requests\Response;

/**
 * The library's adapter with the wait between calls configurable, in hundredths, instead of a hardcoded second
 */
class ThrottledHttpAdapter extends AbstractHttpAdapter
{
    public function __construct(
        ?string $endpoint = null,
        private readonly int $throttle = 100,
    ) {
        // an unusable url keeps the public server, the way the library does, rather than failing every scan
        if ($endpoint !== null && filter_var($endpoint, FILTER_VALIDATE_URL)) {
            $this->endpoint = $endpoint;
        }
    }

    /**
     * Perform an HTTP request on MusicBrainz
     *
     * @param array<string, string> $params
     * @param array<string, string> $options
     * @param bool $returnArray force json_decode to return an array instead of an object
     * @return array<array-key, mixed>|object
     * @throws Exception
     */
    public function call(
        string $path,
        array $params = [],
        array $options = [],
        bool $isAuthRequired = false,
        bool $returnArray = false,
    ): array|object {
        if (($options['user-agent'] ?? null) == '') {
            throw new Exception('You must set a valid User Agent before accessing the MusicBrainz API');
        }

        $url = $this->endpoint . '/' . $path;
        $i   = 0;
        foreach ($params as $name => $value) {
            $url .= ($i++ == 0) ? '?' : '&';
            // AbstractFilter already urlencodes the Lucene escaped Query parts, so don't do it twice
            $url .= $name . '=' . $value;
        }

        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => $options['user-agent'],
        ];

        $requestOptions = [];
        if ($isAuthRequired) {
            if (
                ($options['user'] ?? null) != null
                && ($options['password'] ?? null) != null
            ) {
                $requestOptions['auth'] = [$options['user'], $options['password']];
            } else {
                throw new Exception('Authentication is required');
            }
        }

        $request = $this->request($url, $headers, $requestOptions);

        $this->waitBeforeTheNextCall();

        return json_decode($request->body, $returnArray);
    }

    /**
     * The only line that reaches the network, so a test can watch what call() does around it
     *
     * @param array<string, string> $headers
     * @param array<string, mixed> $options
     */
    protected function request(string $url, array $headers, array $options): Response
    {
        return Requests::get($url, $headers, $options);
    }

    private function waitBeforeTheNextCall(): void
    {
        // a mirror of your own is not bound by the rule the wait exists for, and can answer far below the
        // one call a second the public server allows: the setting counts hundredths, which usleep takes as micro
        if ($this->throttle > 0) {
            usleep($this->throttle * 10000);
        }
    }
}
