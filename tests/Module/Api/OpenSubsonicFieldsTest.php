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

namespace Ampache\Module\Api;

use PHPUnit\Framework\TestCase;

/**
 * Covers the lyric parsing behind the OpenSubsonic `songLyrics` extension.
 *
 * The cue offsets are the delicate part: the spec requires UTF-8 *byte* offsets into the exact string the response
 * carries, so a multi-byte character must not shift a cue off its word. Everything else in OpenSubsonic_Fields reads
 * models whose only seam is `Dba::` and so is verified over HTTP instead.
 */
class OpenSubsonicFieldsTest extends TestCase
{
    public function testParseLyricsBuildsWordCuesWithoutEndTimes(): void
    {
        $parsed = OpenSubsonic_Fields::parseLyrics('[00:12.00]<00:12.00>Hello <00:13.20>cruel <00:14.10>world', true);

        self::assertSame('Hello cruel world', $parsed['line'][0]['value']);

        $cueLine = $parsed['cueLine'][0];
        self::assertSame(0, $cueLine['index']);
        self::assertSame(12000, $cueLine['start']);
        self::assertSame('Hello cruel world', $cueLine['value']);

        self::assertSame(
            [
                ['start' => 12000, 'value' => 'Hello ', 'byteStart' => 0, 'byteEnd' => 5],
                ['start' => 13200, 'value' => 'cruel ', 'byteStart' => 6, 'byteEnd' => 11],
                ['start' => 14100, 'value' => 'world', 'byteStart' => 12, 'byteEnd' => 16],
            ],
            $cueLine['cue']
        );

        // Enhanced LRC carries start-only timing, and the spec forbids an `end` on some cues but not others.
        foreach ($cueLine['cue'] as $cue) {
            self::assertArrayNotHasKey('end', $cue);
        }
    }

    /**
     * The offsets must address bytes rather than characters, so an accented word has to stay addressable.
     */
    public function testParseLyricsCueOffsetsAreUtf8ByteOffsets(): void
    {
        $parsed  = OpenSubsonic_Fields::parseLyrics('[00:01.00]<00:01.00>Café <00:02.00>naïve', true);
        $cueLine = $parsed['cueLine'][0];

        foreach ([0 => 'Café ', 1 => 'naïve'] as $index => $expected) {
            $cue = $cueLine['cue'][$index];
            self::assertSame(
                $expected,
                substr($cueLine['value'], $cue['byteStart'], $cue['byteEnd'] - $cue['byteStart'] + 1)
            );
        }
    }

    public function testParseLyricsIndexesCueLinesAgainstTheLineArray(): void
    {
        $parsed = OpenSubsonic_Fields::parseLyrics(
            "[00:01.00]plain line\n[00:02.00]<00:02.00>timed <00:03.00>line",
            true
        );

        self::assertCount(2, $parsed['line']);
        self::assertCount(1, $parsed['cueLine']);
        self::assertSame(1, $parsed['cueLine'][0]['index']);
        self::assertSame($parsed['line'][1]['value'], $parsed['cueLine'][0]['value']);
    }

    public function testParseLyricsKeepsCueOffsetsAlignedAroundLeadingWhitespace(): void
    {
        $parsed  = OpenSubsonic_Fields::parseLyrics('[00:01.00]<00:01.00>  spaced <00:02.00>out', true);
        $cueLine = $parsed['cueLine'][0];

        self::assertSame('  spaced out', $cueLine['value']);
        self::assertSame(
            '  spaced ',
            substr($cueLine['value'], $cueLine['cue'][0]['byteStart'], $cueLine['cue'][0]['byteEnd'] - $cueLine['cue'][0]['byteStart'] + 1)
        );
    }

    public function testParseLyricsKeepsUntimedLinesWithoutInventingTimestamps(): void
    {
        $parsed = OpenSubsonic_Fields::parseLyrics("[00:02.00]Later\nuntimed\n[00:00.00]First", true);

        self::assertSame(
            [['value' => 'untimed'], ['value' => 'First', 'start' => 0], ['value' => 'Later', 'start' => 2000]],
            $parsed['line']
        );
    }

    public function testParseLyricsNeverInfersExactLineEnds(): void
    {
        $lyrics = "[00:01.00]First\n[00:03.00]<00:03.00>Second";

        foreach ([false, true] as $enhanced) {
            $parsed = OpenSubsonic_Fields::parseLyrics($lyrics, $enhanced);
            foreach ($parsed['line'] as $line) {
                self::assertArrayNotHasKey('end', $line);
            }
        }
    }

    public function testParseLyricsOmitsCueLinesUnlessEnhancedIsRequested(): void
    {
        $lyric = '[00:12.00]<00:12.00>Hello <00:13.20>world';

        self::assertSame([], OpenSubsonic_Fields::parseLyrics($lyric)['cueLine']);
        self::assertNotSame([], OpenSubsonic_Fields::parseLyrics($lyric, true)['cueLine']);
    }

    public function testParseLyricsPreservesUntimedTextAroundCues(): void
    {
        $parsed  = OpenSubsonic_Fields::parseLyrics('[00:01.00]Intro <00:01.00>Café & <00:02.00>tea', true);
        $cueLine = $parsed['cueLine'][0];

        self::assertSame('Intro Café & tea', $cueLine['value']);
        foreach ($cueLine['cue'] as $cue) {
            self::assertSame($cue['value'], substr($cueLine['value'], $cue['byteStart'], $cue['byteEnd'] - $cue['byteStart'] + 1));
        }
    }

    public function testParseLyricsReadsLrcLineTimings(): void
    {
        $parsed = OpenSubsonic_Fields::parseLyrics("[00:12.00]Hello\n[01:02.50]World");

        self::assertTrue($parsed['synced']);
        self::assertSame(
            [
                ['value' => 'Hello', 'start' => 12000],
                ['value' => 'World', 'start' => 62500],
            ],
            $parsed['line']
        );
    }

    public function testParseLyricsSortsLinesAndReindexesCues(): void
    {
        $text   = "[00:04.00]<00:04.00>Fourth\n[00:01.00]First\n[00:02.00]<00:02.00>Second\n[00:02.00]Also second";
        $parsed = OpenSubsonic_Fields::parseLyrics($text, true);

        self::assertSame(['First', 'Second', 'Also second', 'Fourth'], array_column($parsed['line'], 'value'));
        self::assertSame([1000, 2000, 2000, 4000], array_column($parsed['line'], 'start'));
        self::assertSame([1, 3], array_column($parsed['cueLine'], 'index'));
        self::assertSame(['Second', 'Fourth'], array_column($parsed['cueLine'], 'value'));
    }

    public function testParseLyricsTreatsUntimedTextAsUnsynced(): void
    {
        $parsed = OpenSubsonic_Fields::parseLyrics("first line\nsecond line");

        self::assertFalse($parsed['synced']);
        self::assertSame(
            [['value' => 'first line'], ['value' => 'second line']],
            $parsed['line']
        );
        self::assertSame([], $parsed['cueLine']);
    }
}
