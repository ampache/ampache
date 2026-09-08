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

use Ampache\Config\AmpConfig;
use Ampache\Gui\Partial\PageMeta;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BrandingTagsTest extends TestCase
{
    private const array KEYS = ['custom_favicon', 'custom_apple_touch_icon', 'custom_share_image', 'custom_login_background', 'custom_login_logo', 'site_title', 'site_description', 'web_path'];

    private ?string $host = null;

    /** @var array<string, mixed> */
    private array $saved = [];

    public function testACustomRasterFillsEverySlotOnItsOwn(): void
    {
        $tags = $this->tags('https://example.org/logo.png');

        self::assertStringContainsString('rel="icon" href="https://example.org/logo.png"', $tags);
        self::assertStringContainsString('rel="apple-touch-icon" href="https://example.org/logo.png"', $tags);
        self::assertStringContainsString('og:image" content="https://example.org/logo.png"', $tags);
        self::assertStringNotContainsString('favicon.svg', $tags);
    }

    public function testACustomVectorLeavesTheHomeScreenIconOutButStillGetsAPreview(): void
    {
        // an instance that dresses itself must never show Ampache's logo where a visitor reads it
        // as theirs, which a home screen icon is; a link preview would otherwise show nothing at
        // all, and a scraper left to itself settles on whatever image the page happened to hold
        $tags = $this->tags('https://example.org/logo.svg');

        self::assertStringContainsString('href="https://example.org/logo.svg" type="image/svg+xml"', $tags);
        self::assertStringNotContainsString('favicon.ico', $tags);
        self::assertStringNotContainsString('apple-touch-icon', $tags);
        self::assertStringContainsString('og:image" content="/ampache-card.png"', $tags);
    }

    public function testAnObjectPageEmitsItsOwnPreviewAndSilencesTheGenericOne(): void
    {
        PageMeta::set(['Some artist'], 'music.album', 'Some album', 'https://music.example/albums.php?album=1', 'https://music.example/image.php?object_id=1');

        $head = $this->head();

        self::assertSame(1, substr_count($head, 'og:image'));
        self::assertSame(1, substr_count($head, 'og:type'));
        self::assertStringContainsString('og:type" content="music.album"', $head);
        self::assertStringContainsString('og:image" content="https://music.example/image.php?object_id=1&amp;nosvg=1"', $head);
        self::assertStringNotContainsString('ampache-card.png', $head);
    }

    public function testAnUncustomisedInstanceGetsEverythingItShips(): void
    {
        $tags = $this->tags();

        self::assertStringContainsString('/favicon.svg" type="image/svg+xml"', $tags);
        self::assertStringContainsString('/favicon.ico"', $tags);
        self::assertStringContainsString('rel="apple-touch-icon" href="/apple-touch-icon.png"', $tags);
        self::assertStringContainsString('og:image" content="/ampache-card.png"', $tags);
    }

    public function testAPageWithNothingToSayKeepsTheGenericPreview(): void
    {
        $head = $this->head();

        self::assertSame(1, substr_count($head, 'og:image'));
        self::assertStringContainsString('og:image" content="/ampache-card.png"', $head);
        self::assertStringContainsString('og:type" content="website"', $head);
    }

    public function testAQuotedUrlCannotBreakOutOfTheAttribute(): void
    {
        $tags = $this->tags('https://example.org/a".png');

        self::assertStringNotContainsString('a".png', $tags);
        self::assertStringContainsString('a&quot;.png', $tags);
    }

    public function testAVectorIsRecognisedThroughItsQueryString(): void
    {
        // a cache-busting suffix must not turn a vector into a raster
        $tags = $this->tags('https://example.org/logo.svg?v=3');

        self::assertStringContainsString('type="image/svg+xml"', $tags);
        self::assertStringNotContainsString('apple-touch-icon', $tags);
    }

    public function testNothingIsSaidWithoutADescription(): void
    {
        self::assertStringNotContainsString('description', $this->tags());
    }

    public function testOnlyTheWideArtworkEarnsTheBanner(): void
    {
        self::assertStringContainsString('twitter:card" content="summary_large_image"', $this->tags('', '', 'https://example.org/card.png'));
        self::assertStringContainsString('twitter:card" content="summary_large_image"', $this->tags('https://example.org/logo.svg'));
        self::assertStringContainsString('twitter:card" content="summary"', $this->tags('https://example.org/logo.png'));
    }

    public function testTheDedicatedSettingsWinOverTheFavicon(): void
    {
        $tags = $this->tags(
            'https://example.org/logo.svg',
            'https://example.org/touch.png',
            'https://example.org/share.png'
        );

        self::assertStringContainsString('rel="apple-touch-icon" href="https://example.org/touch.png"', $tags);
        self::assertStringContainsString('og:image" content="https://example.org/share.png"', $tags);
    }

    public function testTheDescriptionIsSaidOnceForTheReaderAndOnceForTheScraper(): void
    {
        AmpConfig::set('site_description', 'Free music, freely licensed.', true);

        $tags = $this->tags();

        self::assertStringContainsString('<meta name="description" content="Free music, freely licensed.">', $tags);
        self::assertStringContainsString('og:description" content="Free music, freely licensed.">', $tags);
    }

    public function testTheGenericCardStandsDownForAPageThatSpeaksForItself(): void
    {
        AmpConfig::set('site_title', 'Dogmazic', true);
        AmpConfig::set('site_description', 'Free music, freely licensed.', true);

        $tags = $this->tags('', '', '', false);

        // the icons and the site name belong to the instance, not to whatever the page shows
        self::assertStringContainsString('rel="icon"', $tags);
        self::assertStringContainsString('og:site_name" content="Dogmazic"', $tags);
        self::assertStringNotContainsString('og:image', $tags);
        self::assertStringNotContainsString('og:type', $tags);
        self::assertStringNotContainsString('twitter:card', $tags);
        self::assertStringNotContainsString('description', $tags);
    }

    public function testTheShareImageIsMadeAbsolute(): void
    {
        // whoever renders the preview fetches it from their own server, where a bare path means nothing
        $_SERVER['HTTP_HOST'] = 'music.example';

        self::assertStringContainsString(
            'og:image" content="http://music.example/images/card.png"',
            $this->tags('', '', '/images/card.png')
        );
    }

    protected function setUp(): void
    {
        foreach (self::KEYS as $key) {
            $this->saved[$key] = AmpConfig::get($key);
            AmpConfig::set($key, '', true);
        }

        $this->host = $_SERVER['HTTP_HOST'] ?? null;
        unset($_SERVER['HTTP_HOST']);
    }

    protected function tearDown(): void
    {
        PageMeta::render();
        foreach ($this->saved as $key => $value) {
            AmpConfig::set($key, $value, true);
        }

        if ($this->host === null) {
            unset($_SERVER['HTTP_HOST']);
        } else {
            $_SERVER['HTTP_HOST'] = $this->host;
        }
    }

    private function head(): string
    {
        ob_start();
        Ui::show_custom_style();

        return (string) ob_get_clean();
    }

    private function tags(string $favicon = '', string $touch = '', string $share = '', bool $withSocialCard = true): string
    {
        AmpConfig::set('custom_favicon', $favicon, true);
        AmpConfig::set('custom_apple_touch_icon', $touch, true);
        AmpConfig::set('custom_share_image', $share, true);

        return (string) new ReflectionMethod(Ui::class, 'branding_tags')->invoke(null, $withSocialCard);
    }
}
