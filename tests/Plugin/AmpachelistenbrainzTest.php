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

namespace Ampache\Plugin;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * A configured `listenbrainz_api_url` carrying its own scheme (an http-only mirror on the same subnet, e.g.
 * Koito) used to be concatenated onto the fixed `https` scheme, building the invalid `https://http://...` url
 * from https://github.com/ampache/ampache/issues/4522.
 */
class AmpachelistenbrainzTest extends TestCase
{
    private Ampachelistenbrainz $plugin;

    public function testABareHostKeepsTheDefaultHttpsScheme(): void
    {
        $this->resolveApiUrl('api.listenbrainz.org');

        self::assertSame('https', $this->scheme());
        self::assertSame('api.listenbrainz.org', $this->apiHost());
    }

    public function testAConfiguredHttpsUrlIsUsedAsIs(): void
    {
        $this->resolveApiUrl('https://mb.example.com');

        self::assertSame('https', $this->scheme());
        self::assertSame('mb.example.com', $this->apiHost());
    }

    public function testAConfiguredHttpUrlIsUsedAsIs(): void
    {
        $this->resolveApiUrl('http://10.10.10.10:4110/apis/listenbrainz');

        self::assertSame('http', $this->scheme());
        self::assertSame('10.10.10.10:4110/apis/listenbrainz', $this->apiHost());
    }

    public function testAnEmptyValueLeavesTheDefaultsInPlace(): void
    {
        $this->resolveApiUrl('');

        self::assertSame('https', $this->scheme());
        self::assertSame('api.listenbrainz.org', $this->apiHost());
    }

    public function testATrailingSlashIsDropped(): void
    {
        $this->resolveApiUrl('http://10.10.10.10:4110/apis/listenbrainz/');

        self::assertSame('10.10.10.10:4110/apis/listenbrainz', $this->apiHost());
    }

    protected function setUp(): void
    {
        $this->plugin = new Ampachelistenbrainz();
    }

    private function apiHost(): string
    {
        $property = new ReflectionProperty(Ampachelistenbrainz::class, 'api_host');

        return $property->getValue($this->plugin);
    }

    private function resolveApiUrl(string $configured): void
    {
        $method = new ReflectionMethod(Ampachelistenbrainz::class, '_resolveApiUrl');
        $method->invoke($this->plugin, $configured);
    }

    private function scheme(): string
    {
        $property = new ReflectionProperty(Ampachelistenbrainz::class, 'scheme');

        return $property->getValue($this->plugin);
    }
}
