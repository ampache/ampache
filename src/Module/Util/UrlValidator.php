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

namespace Ampache\Module\Util;

/**
 * Decides whether a url may be fetched by the server
 *
 * Anything a user or a remote feed supplies becomes a request from the server itself, so a url naming the loopback
 * interface, a private network, a link-local address or a cloud metadata endpoint is refused before it is fetched.
 */
final readonly class UrlValidator implements UrlValidatorInterface
{
    /** @var string[] the only schemes the server will fetch */
    private const array SCHEMES = ['http', 'https'];

    public function isPublicHttpUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (
            $parts === false
            || empty($parts['host'])
            || !in_array(strtolower((string) ($parts['scheme'] ?? '')), self::SCHEMES, true)
        ) {
            return false;
        }

        $addresses = $this->resolve($parts['host']);
        if ($addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Every address the host resolves to, so a name answering with one public and one private address is refused
     *
     * @return string[]
     */
    private function resolve(string $host): array
    {
        // an ip literal may be bracketed, as ipv6 is in a url
        $host = trim($host, '[]');
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        return array_merge(
            gethostbynamel($host) ?: [],
            array_column((array) @dns_get_record($host, DNS_AAAA), 'ipv6')
        );
    }
}
