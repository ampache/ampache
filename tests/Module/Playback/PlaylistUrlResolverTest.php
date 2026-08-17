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
 */

namespace Ampache\Module\Playback;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

class PlaylistUrlResolverTest extends TestCase
{
    private PlaylistUrlResolver $subject;

    /**
     * @return list<array{0: string}>
     */
    public static function nonPlaylistUrlDataProvider(): array
    {
        return [
            ['https://ice1.somafm.com/groovesalad-128-mp3.mp3'],
            ['https://example.com/stream.aac'],
            ['https://example.com/live.ogg'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function playlistBodyDataProvider(): array
    {
        return [
            'pls' => [
                "[playlist]\nnumberofentries=3\nFile1=https://ice6.somafm.com/groovesalad-128-mp3\nTitle1=SomaFM\nLength1=-1\nFile2=https://ice2.somafm.com/groovesalad-128-mp3\n",
                'https://ice6.somafm.com/groovesalad-128-mp3',
            ],
            'pls with spaces' => [
                "[playlist]\nFile1 = https://example.com/stream\n",
                'https://example.com/stream',
            ],
            'm3u' => [
                "#EXTM3U\n#EXTINF:-1,Some Station\nhttps://example.com/stream.mp3\n",
                'https://example.com/stream.mp3',
            ],
            'm3u with a blank line first' => [
                "\n\nhttps://example.com/stream.mp3\n",
                'https://example.com/stream.mp3',
            ],
            'mms is a stream too' => [
                "mms://example.com/stream\n",
                'mms://example.com/stream',
            ],
            'relative entries are not streams' => [
                "#EXTM3U\n/local/path.mp3\nanother.mp3\n",
                null,
            ],
            'nothing usable' => [
                "[playlist]\nnumberofentries=0\n",
                null,
            ],
        ];
    }

    /**
     * @param non-empty-string $body
     */
    #[DataProvider('playlistBodyDataProvider')]
    public function testFirstStreamUrlReadsBothPlaylistShapes(string $body, ?string $expected): void
    {
        $method = new ReflectionMethod($this->subject, 'firstStreamUrl');

        self::assertSame($expected, $method->invoke($this->subject, $body));
    }

    /**
     * A station url with no playlist extension is left alone without any request being made.
     */
    #[DataProvider('nonPlaylistUrlDataProvider')]
    public function testResolveLeavesAStreamUrlAlone(string $url): void
    {
        self::assertSame($url, $this->subject->resolve($url));
    }

    protected function setUp(): void
    {
        $this->subject = new PlaylistUrlResolver(
            $this->createMock(LoggerInterface::class)
        );
    }
}
