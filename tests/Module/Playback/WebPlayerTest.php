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

use Ampache\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class WebPlayerTest extends MockeryTestCase
{
    private const string SSID = 'e2d6acb460a2de459967bfc8f01efd4c04cb0c5a';

    /**
     * @return array<string, array{string, string}>
     */
    public static function playUrlProvider(): array
    {
        return [
            'query string' => [
                'https://ampache.test/play/index.php?ssid=' . self::SSID . '&uid=1&demo_id=7',
                'https://ampache.test/play/index.php?ssid=' . self::SSID . '&uid=1&demo_id=7&player=webplayer',
            ],
            'beautiful' => [
                'https://ampache.test/play/ssid/' . self::SSID . '/uid/1/demo_id/7',
                'https://ampache.test/play/index.php?ssid=' . self::SSID . '&uid=1&demo_id=7&player=webplayer',
            ],
            'player already set' => [
                'https://ampache.test/play/index.php?ssid=' . self::SSID . '&uid=1&demo_id=7&player=api',
                'https://ampache.test/play/index.php?ssid=' . self::SSID . '&uid=1&demo_id=7&player=api',
            ],
            'not a play url' => [
                'https://radio.example/stream.ogg?demo_id=7',
                'https://radio.example/stream.ogg?demo_id=7',
            ],
        ];
    }

    #[DataProvider('playUrlProvider')]
    public function testGetMediaJsParamMarksPlayUrlsAsTheWebPlayer(string $url, string $expected): void
    {
        $item = new Stream_Url(['url' => $url, 'type' => 'democratic', 'title' => 'Democratic', 'author' => '']);

        $json = json_decode(WebPlayer::get_media_js_param($item, 'default'), true);

        self::assertIsArray($json);
        self::assertSame($expected, $json['url']);
    }
}
