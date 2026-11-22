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
            ['http://100.100.100.200/latest/meta-data/', false],
            ['http://[::1]/', false],
            ['http://[fd00::1]/', false],
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

    /**
     * @return list<array{0: string, 1: bool}>
     */
    public static function lanUrlDataProvider(): array
    {
        return [
            ['http://10.10.10.10:4110/apis/listenbrainz', true],
            ['http://192.168.1.10/feed.xml', true],
            ['http://[fd00::1]/', true],
            ['http://93.184.216.34/feed.xml', true],
            ['http://127.0.0.1:8080/admin', false],
            ['http://169.254.169.254/latest/meta-data/', false],
            ['http://100.100.100.200/latest/meta-data/', false],
            ['http://[fd00:ec2::254]/latest/meta-data/', false],
            ['http://[::1]/', false],
            ['http://[fe80::1]/', false],
            ['http://0.0.0.0/', false],
            ['file:///etc/passwd', false],
        ];
    }

    // a self-hosted service on the lan passes, while the addresses that reach the server itself still don't
    #[DataProvider('lanUrlDataProvider')]
    public function testIsPublicHttpUrlAllowingLan(string $url, bool $expected): void
    {
        self::assertSame($expected, $this->subject->isPublicHttpUrl($url, true), $url);
    }

    public function testResolvePinnedTarget(): void
    {
        self::assertSame(
            ['host' => '10.10.10.10', 'port' => 4110, 'address' => '10.10.10.10'],
            $this->subject->resolvePinnedTarget('http://10.10.10.10:4110/apis/listenbrainz', true)
        );
        self::assertSame(
            ['host' => '2606:2800:220:1:248:1893:25c8:1946', 'port' => 443, 'address' => '2606:2800:220:1:248:1893:25c8:1946'],
            $this->subject->resolvePinnedTarget('https://[2606:2800:220:1:248:1893:25c8:1946]/feed.xml')
        );
        self::assertNull($this->subject->resolvePinnedTarget('http://10.10.10.10:4110/apis/listenbrainz'));
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

    protected function setUp(): void
    {
        $this->subject = new UrlValidator();
    }
}
