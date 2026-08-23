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
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BrandingTagsTest extends TestCase
{
    public function testACustomRasterFillsEverySlotOnItsOwn(): void
    {
        $tags = $this->tags('https://example.org/logo.png');

        self::assertStringContainsString('rel="icon" href="https://example.org/logo.png"', $tags);
        self::assertStringContainsString('rel="apple-touch-icon" href="https://example.org/logo.png"', $tags);
        self::assertStringContainsString('og:image" content="https://example.org/logo.png"', $tags);
        self::assertStringNotContainsString('favicon.svg', $tags);
    }

    public function testACustomVectorIsUsedAloneAndNothingShippedFillsTheGaps(): void
    {
        // an instance that dresses itself must never show Ampache's logo beside its own, so the
        // slots a vector cannot fill are left empty rather than filled with somebody else's icon
        $tags = $this->tags('https://example.org/logo.svg');

        self::assertStringContainsString('href="https://example.org/logo.svg" type="image/svg+xml"', $tags);
        self::assertStringNotContainsString('favicon.ico', $tags);
        self::assertStringNotContainsString('apple-touch-icon', $tags);
        self::assertStringNotContainsString('og:image', $tags);
    }

    public function testAnUncustomisedInstanceGetsEverythingItShips(): void
    {
        $tags = $this->tags();

        self::assertStringContainsString('/favicon.svg" type="image/svg+xml"', $tags);
        self::assertStringContainsString('/favicon.ico"', $tags);
        self::assertStringContainsString('rel="apple-touch-icon" href="/images/apple-touch-icon.png"', $tags);
        self::assertStringContainsString('og:image" content="/images/ampache-card.png"', $tags);
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

    private function tags(string $favicon = '', string $touch = '', string $share = ''): string
    {
        AmpConfig::set('custom_favicon', $favicon, true);
        AmpConfig::set('custom_apple_touch_icon', $touch, true);
        AmpConfig::set('custom_share_image', $share, true);

        return (string) new ReflectionMethod(Ui::class, 'branding_tags')->invoke(null);
    }
}
