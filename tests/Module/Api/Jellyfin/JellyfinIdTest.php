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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JellyfinIdTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function malformedIdProvider(): array
    {
        return [
            'empty' => [''],
            'bare numeric (Subsonic OLD_SUBID style)' => ['300000001'],
            'subsonic-style prefix' => ['so-1234'],
            'too short' => ['1234'],
            'wrong dash positions' => ['12345678-1234-1234-1234-1234567890123'],
            'non-hex characters' => ['zzzzzzzz-zzzz-4zzz-8zzz-zzzzzzzzzzzz'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function typeProvider(): array
    {
        return [
            'song' => ['song', 1],
            'album' => ['album', 1234],
            'album_disk (reserved)' => ['album_disk', 42],
            'artist' => ['artist', 99],
            'catalog' => ['catalog', 3],
            'folder' => ['folder', 500],
            'genre' => ['genre', 7],
            'live_stream' => ['live_stream', 2],
            'playlist' => ['playlist', 10],
            'podcast (reserved)' => ['podcast', 8],
            'podcast_episode (reserved)' => ['podcast_episode', 88],
            'search' => ['search', 55],
            'user' => ['user', 1],
            'video (reserved)' => ['video', 6],
            'view (synthetic)' => ['view', 1],
            'label' => ['label', 12],
            'year (synthetic)' => ['year', 2024],
            'zero id' => ['song', 0],
            'large id' => ['song', 4294967295],
        ];
    }

    public function testARealRandomUuidWithoutTheMagicByteIsRejected(): void
    {
        // a genuine random v4 UUID a client might send by mistake — correct shape, wrong (missing) magic byte
        $randomUuid = 'e4eaaaf2-d142-49b2-8b4a-c9a6e6e5f3b1';

        self::assertNull(JellyfinId::decodeId($randomUuid));
        self::assertNull(JellyfinId::decodeType($randomUuid));
    }

    #[DataProvider('typeProvider')]
    public function testDebugStringRoundTripsToThePrefixedLabel(string $type, int $id): void
    {
        $encoded = JellyfinId::encode($type, $id);

        self::assertNotNull($encoded);
        self::assertMatchesRegularExpression('/^[a-z]{2}-\d+$/', JellyfinId::toDebugString($encoded));
    }

    public function testDecodeObjectReturnsNullForAnUnrecognisedId(): void
    {
        self::assertNull(JellyfinId::decodeObject('not-a-valid-id'));
    }

    #[DataProvider('typeProvider')]
    public function testEncodedFormIsARfc4122ShapedUuid(string $type, int $id): void
    {
        $encoded = JellyfinId::encode($type, $id);

        self::assertNotNull($encoded);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $encoded,
            'must be a syntactically valid UUID with version 4 and a valid RFC 4122 variant nibble',
        );
    }

    #[DataProvider('malformedIdProvider')]
    public function testMalformedInputIsRejected(string $jfId): void
    {
        self::assertNull(JellyfinId::decodeId($jfId));
        self::assertNull(JellyfinId::decodeType($jfId));
        self::assertNull(JellyfinId::decodeObject($jfId));
    }

    public function testNegativeIdIsRejected(): void
    {
        self::assertNull(JellyfinId::encode('song', -1));
    }

    #[DataProvider('typeProvider')]
    public function testRoundTripsIdAndType(string $type, int $id): void
    {
        $encoded = JellyfinId::encode($type, $id);

        self::assertNotNull($encoded);
        self::assertSame($id, JellyfinId::decodeId($encoded));
        self::assertSame($type, JellyfinId::decodeType($encoded));
        self::assertTrue(JellyfinId::isType($encoded, $type));
    }

    public function testUnknownTypeIsRejected(): void
    {
        self::assertNull(JellyfinId::encode('not_a_real_type', 1));
    }
}
