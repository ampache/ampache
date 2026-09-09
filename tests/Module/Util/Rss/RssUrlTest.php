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

namespace Ampache\Module\Util\Rss;

use Ampache\Config\AmpConfig;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RssUrlTest extends TestCase
{
    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function slugDataProvider(): array
    {
        return [
            ['a plain name is just lowercased and hyphenated', 'Affectivity (2003)', 'affectivity-2003'],
            ['markup is dropped, not spelled out', '<a href="/artists.php?action=show&artist=7">Meme</a>', 'meme'],
            ['entities are decoded before the ascii pass', 'Caf&eacute; &amp; Cr&egrave;me', 'cafe-creme'],
            ['accents are transliterated', 'Été à Paris', 'ete-a-paris'],
            ['a name made only of markup yields nothing', '<br/><span></span>', ''],
            ['an empty name yields nothing', '', ''],
            ['no leading or trailing separator survives', '  --Hello--  ', 'hello'],
        ];
    }

    public function testPublishedAppendsTheSlugOnlyForAnObject(): void
    {
        AmpConfig::set('rss_beautiful_url', true, true);

        $withObject = RssUrl::published(
            ['type' => 'library_item', 'object_type' => 'album_disk', 'object_id' => '3183'],
            'Affectivity (2003)'
        );
        $withoutObject = RssUrl::published(['type' => 'now_playing'], 'Affectivity (2003)');

        self::assertStringEndsWith('/rss/album-disk/3183/affectivity-2003', $withObject);
        self::assertStringEndsWith('/rss/now-playing', $withoutObject);
    }

    public function testPublishedFallsBackToTheQueryFormWhenBeautifulUrlsAreOff(): void
    {
        AmpConfig::set('rss_beautiful_url', false, true);

        $url = RssUrl::published(
            ['type' => 'library_item', 'object_type' => 'album_disk', 'object_id' => '3183'],
            'Affectivity (2003)'
        );

        self::assertStringContainsString('/rss.php?', $url);
        self::assertStringNotContainsString('affectivity', $url);
    }

    public function testPublishedKeepsMarkupOutOfThePath(): void
    {
        AmpConfig::set('rss_beautiful_url', true, true);

        $url = RssUrl::published(
            ['type' => 'library_item', 'object_type' => 'album_disk', 'object_id' => '3183'],
            'Unknown&nbsp;-&nbsp;<a href="https://example.org/artists.php?action=show&artist=2847" title="Ultraviolette Cappuccino">Ultraviolette Cappuccino</a>'
        );

        self::assertStringNotContainsString('href', $url);
        self::assertStringNotContainsString('nbsp', $url);
        self::assertStringNotContainsString('artists-php', $url);
        self::assertStringEndsWith('/rss/album-disk/3183/unknown-ultraviolette-cappuccino', $url);
    }

    #[DataProvider('slugDataProvider')]
    public function testSlug(string $case, string $input, string $expected): void
    {
        self::assertSame($expected, RssUrl::slug($input), $case);
    }

    public function testSlugIsBounded(): void
    {
        $slug = RssUrl::slug(str_repeat('titre interminable ', 40));

        self::assertLessThanOrEqual(96, strlen($slug));
        self::assertStringEndsNotWith('-', $slug);
    }

    #[Override]
    protected function tearDown(): void
    {
        AmpConfig::set('rss_beautiful_url', false, true);

        parent::tearDown();
    }
}
