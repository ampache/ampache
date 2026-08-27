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

namespace Ampache\Gui\Partial;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PageMetaTest extends TestCase
{
    /**
     * @return list<array{int, string}>
     */
    public static function durations(): array
    {
        return [
            [0, ''],
            [56, 'PT56S'],
            [236, 'PT3M56S'],
            [3725, 'PT1H2M5S'],
        ];
    }

    #[DataProvider('durations')]
    public function testDurationSpeaksIso8601(int $seconds, string $expected): void
    {
        static::assertSame($expected, PageMeta::duration($seconds));
    }

    public function testEscapesAndDecodesWhatThePageSets(): void
    {
        PageMeta::set(
            ['R&amp;B', '<b>3:56</b>', '', null],
            'music.song',
            'A "quoted" title',
            'https://x/song.php?a=1&b=2',
            'https://x/i.png',
            ['@type' => 'MusicRecording', 'name' => '</script><script>alert(1)</script>']
        );

        $html = PageMeta::render();

        static::assertStringContainsString('content="R&amp;B · 3:56"', $html);
        static::assertStringContainsString('content="A &quot;quoted&quot; title"', $html);
        static::assertStringContainsString('content="https://x/song.php?a=1&amp;b=2"', $html);
        static::assertStringNotContainsString('</script><script>', substr($html, (int) strpos($html, 'ld+json')));
        static::assertStringContainsString('twitter:card', $html);
    }

    public function testRendersNothingWhenNoPageSetAnything(): void
    {
        static::assertSame('', PageMeta::render());
    }
}
