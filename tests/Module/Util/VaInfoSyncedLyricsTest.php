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

use PHPUnit\Framework\TestCase;

/**
 * Covers VaInfo::parse_synced_lyrics(), which turns a getID3-decoded SYLT frame into LRC-style text.
 *
 * getID3 stores a SYLT frame as one timestamp per lyrics() entry rather than one block of text, and the
 * timestamp is only meaningful when timestampformat is 2 (milliseconds); format 1 (MPEG frame count) would
 * need the stream's frame duration to convert, which this deliberately does not attempt.
 */
class VaInfoSyncedLyricsTest extends TestCase
{
    private const string BASE_ENCODING = 'UTF-8';

    public function testAFrameWhoseContentIsNotLyricsIsSkipped(): void
    {
        $frame                  = $this->sylt([['data' => 'Ignored', 'timestamp' => 0]]);
        $frame['contenttypeid'] = 0;

        $this->assertNull(VaInfo::parse_synced_lyrics([$frame], self::BASE_ENCODING));
    }

    public function testAFrameWithMpegFrameCountTimestampsIsSkipped(): void
    {
        $frame                     = $this->sylt([['data' => 'Ignored', 'timestamp' => 0]]);
        $frame['timestampformat']  = 1;

        $this->assertNull(VaInfo::parse_synced_lyrics([$frame], self::BASE_ENCODING));
    }

    public function testAnEmbeddedNewlineIsCollapsedToASpaceRatherThanSplitIntoAnUnstampedLine(): void
    {
        $frames = [$this->sylt([['data' => "Hello\nWorld", 'timestamp' => 0]])];

        $this->assertSame(
            '[00:00.00]Hello World',
            VaInfo::parse_synced_lyrics($frames, self::BASE_ENCODING)
        );
    }

    public function testAnEmptyFrameListProducesNoLyrics(): void
    {
        $this->assertNull(VaInfo::parse_synced_lyrics([], self::BASE_ENCODING));
    }

    public function testAnEntryThatIsBlankAfterTrimmingIsDropped(): void
    {
        $frames = [
            $this->sylt([
                ['data' => '   ', 'timestamp' => 0],
                ['data' => 'Real line', 'timestamp' => 1000],
            ]),
        ];

        $this->assertSame(
            '[00:01.00]Real line',
            VaInfo::parse_synced_lyrics($frames, self::BASE_ENCODING)
        );
    }

    public function testAnEntryWithNoTimestampIsTreatedAsZero(): void
    {
        $frames = [$this->sylt([['data' => 'No timestamp']])];

        $this->assertSame(
            '[00:00.00]No timestamp',
            VaInfo::parse_synced_lyrics($frames, self::BASE_ENCODING)
        );
    }

    public function testMillisecondTimestampsBecomeLrcLines(): void
    {
        $frames = [
            $this->sylt([
                ['data' => 'Line one', 'timestamp' => 0],
                ['data' => 'Line two', 'timestamp' => 1500],
                ['data' => 'Line three', 'timestamp' => 63250],
            ]),
        ];

        $this->assertSame(
            "[00:00.00]Line one\n[00:01.50]Line two\n[01:03.25]Line three",
            VaInfo::parse_synced_lyrics($frames, self::BASE_ENCODING)
        );
    }

    public function testTextIsConvertedFromTheFramesOwnEncoding(): void
    {
        $frames = [
            $this->sylt([['data' => "caf\xe9", 'timestamp' => 0]], 'ISO-8859-1'),
        ];

        $this->assertSame(
            "[00:00.00]caf\u{e9}",
            VaInfo::parse_synced_lyrics($frames, self::BASE_ENCODING)
        );
    }

    public function testTheFirstUsableFrameWinsWhenSeveralArePresent(): void
    {
        $unusable                    = $this->sylt([['data' => 'Ignored', 'timestamp' => 0]]);
        $unusable['timestampformat'] = 1;
        $usable                      = $this->sylt([['data' => 'Used', 'timestamp' => 0]]);

        $this->assertSame(
            '[00:00.00]Used',
            VaInfo::parse_synced_lyrics([$unusable, $usable], self::BASE_ENCODING)
        );
    }

    /**
     * Builds a getID3-shaped SYLT frame: content type 1 ('lyrics'), timestamp format 2 (milliseconds).
     * @param array<int, array{data: string, timestamp?: int}> $lyrics
     * @return array<string, mixed>
     */
    private function sylt(array $lyrics, string $encoding = 'UTF-8'): array
    {
        return [
            'contenttypeid' => 1,
            'timestampformat' => 2,
            'encoding' => $encoding,
            'lyrics' => $lyrics,
        ];
    }
}
