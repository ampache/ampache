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
use Ampache\Repository\Model\LyricsSourceEnum;
use Ampache\Repository\Model\Song;

class LyricsViewTest extends MockeryTestCase
{
    public function testClearKeepsThePlainWarningForLyricsFromTheFile(): void
    {
        $song                = $this->mock(Song::class);
        $song->lyrics_source = LyricsSourceEnum::FILE;

        $subject = new LyricsView('https://music.example', $song, ['text' => 'Tagged'], true, true);

        $this->assertFalse($subject->isUserSet());
        $this->assertSame('Clear the stored lyrics?', $subject->getClearConfirmation());
    }

    public function testClearWarnsThatUserSetLyricsWillBeLost(): void
    {
        $song                = $this->mock(Song::class);
        $song->lyrics_source = LyricsSourceEnum::USER;

        $subject = new LyricsView('https://music.example', $song, ['text' => 'Typed'], true, true);

        $this->assertTrue($subject->isUserSet());
        $this->assertSame('These lyrics were set by hand and will be lost. Clear them?', $subject->getClearConfirmation());
    }

    public function testGetCandidatesSkipsAnswersWithoutText(): void
    {
        $subject = new LyricsView(
            'https://music.example',
            $this->mock(Song::class),
            [],
            false,
            true,
            [
                'Lyrist' => ['text' => '', 'url' => 'https://lyrist.example/1'],
                'LrcLib' => ['text' => "[00:01.00]Hello", 'url' => ''],
                'ChartLyrics' => ['text' => 'Hello', 'url' => 'https://chart.example/1'],
            ]
        );

        $this->assertSame(
            [
                ['plugin' => 'LrcLib', 'text' => '[00:01.00]Hello', 'url' => null],
                ['plugin' => 'ChartLyrics', 'text' => 'Hello', 'url' => 'https://chart.example/1'],
            ],
            $subject->getCandidates()
        );
    }
}
