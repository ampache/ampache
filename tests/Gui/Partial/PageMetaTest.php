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

use Ampache\Config\AmpConfig;
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

    public function testAppendsNoSvgToTheBeautifiedArtRouteToo(): void
    {
        PageMeta::set([], 'profile', 'An artist', 'https://x/artists.php?artist=1', 'https://x/play/art/abc123/artist/1/size600x600.png');

        self::assertStringContainsString(
            'og:image" content="https://x/play/art/abc123/artist/1/size600x600.png&amp;nosvg=1"',
            PageMeta::render()
        );
    }

    public function testAsksArtForTheRasterSinceAScraperRendersNoSvg(): void
    {
        PageMeta::set([], 'music.album', 'An album', 'https://x/albums.php?album=1', 'https://x/image.php?object_id=1&object_type=album&size=600x600');

        self::assertStringContainsString(
            'og:image" content="https://x/image.php?object_id=1&amp;object_type=album&amp;size=600x600&amp;nosvg=1"',
            PageMeta::render()
        );
    }

    #[DataProvider('durations')]
    public function testDurationSpeaksIso8601(int $seconds, string $expected): void
    {
        self::assertSame($expected, PageMeta::duration($seconds));
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

        self::assertStringContainsString('content="R&amp;B · 3:56"', $html);
        self::assertStringContainsString('content="A &quot;quoted&quot; title"', $html);
        self::assertStringContainsString('content="https://x/song.php?a=1&amp;b=2"', $html);
        self::assertStringNotContainsString('</script><script>', substr($html, (int) strpos($html, 'ld+json')));
        self::assertStringContainsString('twitter:card', $html);
    }

    public function testFallsBackToTheSiteDescriptionWhenTheObjectHasNone(): void
    {
        AmpConfig::set('site_description', 'A shared music server', true);

        PageMeta::set([], 'website', 'A folder', 'https://x/folders.php?folder=1', 'https://x/image.php?object_id=1&object_type=folder&size=600x600');

        self::assertStringContainsString('name="description" content="A shared music server"', PageMeta::render());
    }

    public function testLeavesAnImageThatIsNotServedByArtAlone(): void
    {
        PageMeta::set([], 'music.album', 'An album', 'https://x/albums.php?album=1', 'https://x/themes/reborn/images/logo.png');

        self::assertStringContainsString('og:image" content="https://x/themes/reborn/images/logo.png"', PageMeta::render());
    }

    public function testRendersNothingWhenNoPageSetAnything(): void
    {
        self::assertSame('', PageMeta::render());
    }

    protected function tearDown(): void
    {
        AmpConfig::set('site_description', null, true);
    }
}
