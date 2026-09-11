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
    /**
     * @var array<int, array{0: string, 1: int}> IPv4 ranges FILTER_FLAG_NO_RES_RANGE does not reject: carrier-grade
     * NAT (RFC 6598) and the benchmarking range (RFC 2544), both publicly routable in places and easy to overlook
     */
    private const array EXTRA_BLOCKED_IPV4_RANGES = [
        ['100.64.0.0', 10],
        ['198.18.0.0', 15],
    ];

    /** @var string the IPv4-mapped IPv6 range embedding an IPv4 destination in its last 32 bits (::ffff:0:0/96) */
    private const string IPV4_MAPPED_PREFIX = '::ffff:0:0';

    /** @var string the well-known NAT64 range embedding an IPv4 destination in its last 32 bits (64:ff9b::/96) */
    private const string NAT64_PREFIX = '64:ff9b::';
    /** @var string[] the only schemes the server will fetch */
    private const array SCHEMES = ['http', 'https'];

    public function isPublicHttpUrl(string $url): bool
    {
        return $this->resolvePinnedTarget($url) !== null;
    }

    public function resolvePinnedTarget(string $url): ?array
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, self::SCHEMES, true)) {
            return null;
        }

        $addresses = $this->resolve($parts['host']);
        if ($addresses === [] || !array_all($addresses, $this->isAllowedAddress(...))) {
            return null;
        }

        return [
            // bracketed in a url, but CURLOPT_RESOLVE (and gethostbynamel above) both want it bare
            'host' => trim($parts['host'], '[]'),
            'port' => $parts['port'] ?? ($scheme === 'https' ? 443 : 80),
            'address' => $addresses[0],
        ];
    }

    /**
     * The IPv4 address embedded in an IPv4-mapped or NAT64 IPv6 literal, or null when the address is neither form
     *
     * Compares packed binary rather than the textual prefix, because the embedded address can be spelled as a
     * dotted quad (64:ff9b::10.0.0.1) or as plain hex groups (64:ff9b::a00:1) for the same bytes on the wire.
     */
    private function extractEmbeddedIpv4(string $address): ?string
    {
        $binary = @inet_pton($address);
        if ($binary === false || strlen($binary) !== 16) {
            return null;
        }

        $ipv4MappedPrefix  = inet_pton(self::IPV4_MAPPED_PREFIX);
        $nat64Prefix       = inet_pton(self::NAT64_PREFIX);
        if ($ipv4MappedPrefix === false || $nat64Prefix === false) {
            return null;
        }

        $prefix = substr($binary, 0, 12);
        if ($prefix !== substr($ipv4MappedPrefix, 0, 12) && $prefix !== substr($nat64Prefix, 0, 12)) {
            return null;
        }

        $unpacked = unpack('N', substr($binary, 12, 4));

        return ($unpacked !== false) ? long2ip($unpacked[1]) : null;
    }

    /**
     * Whether a resolved address may be fetched, checking any IPv4 address embedded in an IPv6 transition form
     * as well as the address itself, so an address blocked in its IPv4 spelling can't slip past as ::ffff:a.b.c.d
     * or a NAT64 literal
     */
    private function isAllowedAddress(string $address): bool
    {
        if (!$this->isAllowedByPolicy($address)) {
            return false;
        }

        $embedded = $this->extractEmbeddedIpv4($address);

        return $embedded === null || $this->isAllowedByPolicy($embedded);
    }

    private function isAllowedByPolicy(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        foreach (self::EXTRA_BLOCKED_IPV4_RANGES as [$network, $prefixLength]) {
            if ($this->isInIpv4Range($address, $network, $prefixLength)) {
                return false;
            }
        }

        return true;
    }

    private function isInIpv4Range(string $address, string $network, int $prefixLength): bool
    {
        $addressLong = ip2long($address);
        $networkLong = ip2long($network);
        if ($addressLong === false || $networkLong === false) {
            return false;
        }

        $mask = -1 << (32 - $prefixLength);

        return ($addressLong & $mask) === ($networkLong & $mask);
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
