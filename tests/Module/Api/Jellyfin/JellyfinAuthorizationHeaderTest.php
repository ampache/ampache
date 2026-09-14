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

namespace Ampache\Module\Api\Jellyfin;

use PHPUnit\Framework\TestCase;

class JellyfinAuthorizationHeaderTest extends TestCase
{
    public function testAnEmptyStringAuthorizationHeaderFallsThroughToTheNextSource(): void
    {
        $header = JellyfinAuthorizationHeader::parse('', null, 'bare-token');

        self::assertSame('bare-token', $header->token);
    }

    public function testAuthorizationTakesPrecedenceOverTheLegacyHeader(): void
    {
        $header = JellyfinAuthorizationHeader::parse(
            'MediaBrowser Client="Primary", Token="primary-token"',
            'Emby Client="Fallback", Token="fallback-token"',
        );

        self::assertSame('Primary', $header->client);
        self::assertSame('primary-token', $header->token);
    }

    public function testEverythingIsNullWhenNoHeaderIsPresentAtAll(): void
    {
        $header = JellyfinAuthorizationHeader::parse(null);

        self::assertNull($header->client);
        self::assertNull($header->device);
        self::assertNull($header->deviceId);
        self::assertNull($header->version);
        self::assertNull($header->token);
    }

    public function testFallsBackToTheBareTokenHeaderWhenNeitherCompoundHeaderIsPresent(): void
    {
        $header = JellyfinAuthorizationHeader::parse(null, null, 'bare-token');

        self::assertNull($header->client);
        self::assertNull($header->device);
        self::assertNull($header->deviceId);
        self::assertNull($header->version);
        self::assertSame('bare-token', $header->token);
    }

    public function testMissingFieldsWithinAPresentHeaderAreNull(): void
    {
        $header = JellyfinAuthorizationHeader::parse('MediaBrowser Client="OnlyClient"');

        self::assertSame('OnlyClient', $header->client);
        self::assertNull($header->device);
        self::assertNull($header->deviceId);
        self::assertNull($header->version);
        self::assertNull($header->token);
    }

    public function testParsesAllFieldsFromTheMediaBrowserScheme(): void
    {
        $header = JellyfinAuthorizationHeader::parse(
            'MediaBrowser Client="Finamp", Device="Pixel 7", DeviceId="abc-123", Version="1.2.3", Token="secret-token"',
        );

        self::assertSame('Finamp', $header->client);
        self::assertSame('Pixel 7', $header->device);
        self::assertSame('abc-123', $header->deviceId);
        self::assertSame('1.2.3', $header->version);
        self::assertSame('secret-token', $header->token);
    }

    public function testParsesTheLegacyEmbySchemeTheSameWay(): void
    {
        $header = JellyfinAuthorizationHeader::parse(
            null,
            'Emby Client="OldClient", Device="Old Device", DeviceId="xyz", Version="0.9", Token="tok"',
        );

        self::assertSame('OldClient', $header->client);
        self::assertSame('tok', $header->token);
    }
}
