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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlValidatorTest extends TestCase
{
    private UrlValidator $subject;

    /**
     * @return list<array{0: string, 1: array{host: string, port: int, address: string}|null}>
     */
    public static function pinnedTargetDataProvider(): array
    {
        return [
            ['http://93.184.216.34/feed.xml', ['host' => '93.184.216.34', 'port' => 80, 'address' => '93.184.216.34']],
            ['https://93.184.216.34/feed.xml', ['host' => '93.184.216.34', 'port' => 443, 'address' => '93.184.216.34']],
            ['https://93.184.216.34:8443/feed.xml', ['host' => '93.184.216.34', 'port' => 8443, 'address' => '93.184.216.34']],
            ['https://[2606:2800:220:1:248:1893:25c8:1946]/feed.xml', ['host' => '2606:2800:220:1:248:1893:25c8:1946', 'port' => 443, 'address' => '2606:2800:220:1:248:1893:25c8:1946']],
            ['http://127.0.0.1/', null],
            ['http://169.254.169.254/latest/meta-data/', null],
            ['http://100.64.2.1/a.png', null],
            ['http://198.18.2.1/a.png', null],
            ['http://[64:ff9b::a00:1]/a.png', null],
            ['not-a-url', null],
        ];
    }

    /**
     * Only ip literals are used, so the test needs no name resolution
     *
     * @return list<array{0: string, 1: bool}>
     */
    public static function urlDataProvider(): array
    {
        return [
            ['http://93.184.216.34/feed.xml', true],
            ['https://93.184.216.34:8443/feed.xml', true],
            ['https://[2606:2800:220:1:248:1893:25c8:1946]/feed.xml', true],
            // loopback, private, link-local and the cloud metadata address
            ['http://127.0.0.1/', false],
            ['http://127.0.0.1:8080/admin', false],
            ['https://10.0.0.5/feed.xml', false],
            ['http://172.16.4.1/', false],
            ['http://192.168.1.10/feed.xml', false],
            ['http://169.254.169.254/latest/meta-data/', false],
            ['http://[::1]/', false],
            ['http://[fd00::1]/', false],
            // carrier-grade NAT (RFC 6598), benchmarking (RFC 2544) and NAT64/IPv4-mapped transition forms
            ['http://100.64.2.1/a.png', false],
            ['http://100.127.255.254/a.png', false],
            ['http://198.18.2.1/a.png', false],
            ['http://198.19.255.254/a.png', false],
            ['http://[64:ff9b::a00:1]/episode.mp3', false],
            ['http://[64:ff9b::10.0.0.1]/episode.mp3', false],
            ['http://[::ffff:10.0.0.1]/episode.mp3', false],
            // a public address is not rejected merely for resembling a transition form (93.184.216.34 embedded)
            ['http://[64:ff9b::5db8:d822]/a.png', true],
            // schemes the server must not fetch
            ['file:///etc/passwd', false],
            ['ftp://93.184.216.34/x', false],
            ['gopher://93.184.216.34:70/_x', false],
            ['dict://93.184.216.34:2628/x', false],
            // nothing to resolve
            ['http://', false],
            ['not-a-url', false],
            ['', false],
        ];
    }

    #[DataProvider('urlDataProvider')]
    public function testIsPublicHttpUrl(string $url, bool $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->isPublicHttpUrl($url),
            $url
        );
    }

    /**
     * @param array{host: string, port: int, address: string}|null $expected
     */
    #[DataProvider('pinnedTargetDataProvider')]
    public function testResolvePinnedTarget(string $url, ?array $expected): void
    {
        self::assertSame($expected, $this->subject->resolvePinnedTarget($url), $url);
    }

    protected function setUp(): void
    {
        $this->subject = new UrlValidator();
    }
}
