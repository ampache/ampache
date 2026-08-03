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

use Ampache\Config\AmpConfig;
use Ampache\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class Stream_UrlTest extends MockeryTestCase
{
    private const string SSID = 'e2d6acb460a2de459967bfc8f01efd4c04cb0c5a';

    /**
     * `Stream_Playlist::create_localplay()` enables Localplay repeat for a democratic url so the player re-requests
     * it and receives the next voted song, and it identifies one through this parser
     *
     * @return list<array{bool, string, string}>
     */
    public static function democraticUrlProvider(): array
    {
        return [
            [false, 'https://ampache.test/play/index.php?ssid=' . self::SSID . '&uid=1&demo_id=1', '1'],
            [true, 'https://ampache.test/play/ssid/' . self::SSID . '/uid/1/demo_id/1', '1'],
            [false, 'https://ampache.test/play/index.php?ssid=' . self::SSID . '&uid=1&demo_id=7', '7'],
            [true, 'https://ampache.test/play/ssid/' . self::SSID . '/uid/1/demo_id/7', '7'],
            [true, 'https://ampache.test/play/ssid/' . self::SSID . '/uid/1/demo_id/42', '42'],
        ];
    }

    /**
     * @return list<array{bool, string}>
     */
    public static function nonDemocraticUrlProvider(): array
    {
        return [
            [false, 'https://ampache.test/play/index.php?ssid=' . self::SSID . '&type=song&oid=280&uid=1'],
            [true, 'https://ampache.test/play/ssid/' . self::SSID . '/type/song/oid/280/uid/1'],
            [true, 'https://ampache.test/play/ssid/' . self::SSID . '/uid/1/random/1/random_type/album/random_id/42'],
        ];
    }

    public function testGetTitleTranslatesDemocraticFromABeautifulUrl(): void
    {
        AmpConfig::set('stream_beautiful_url', true, true);

        self::assertSame(
            'Democratic',
            Stream_Url::get_title('https://ampache.test/play/ssid/' . self::SSID . '/uid/1/demo_id/7')
        );
    }

    #[DataProvider('democraticUrlProvider')]
    public function testParseDetectsDemocraticForAnyIdAndUrlStyle(bool $beautiful, string $url, string $expectedId): void
    {
        AmpConfig::set('stream_beautiful_url', $beautiful, true);

        $result = Stream_Url::parse($url);

        self::assertSame('democratic', $result['type'] ?? null);
        self::assertSame($expectedId, $result['demo_id'] ?? null);
    }

    #[DataProvider('nonDemocraticUrlProvider')]
    public function testParseDoesNotReportDemocraticForOtherUrls(bool $beautiful, string $url): void
    {
        AmpConfig::set('stream_beautiful_url', $beautiful, true);

        self::assertNotSame('democratic', Stream_Url::parse($url)['type'] ?? null);
    }
}
