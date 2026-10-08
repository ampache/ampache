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

namespace Ampache\Gui\Playback;

use Ampache\Config\AmpConfig;
use PHPUnit\Framework\TestCase;

/**
 * The card a frame gets when this server has no player for it.
 *
 * A scraper stores the `embed=1` url once and keeps asking for it, so this page is what an operator who
 * switched the player off hands to every post already published. It is framed by a stranger's site, which
 * is why it carries nothing of ours but a link.
 */
class MediaEmbedUnavailableViewTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $config = [];

    public function testAnOperatorWithNoLogoIsNamedInsteadOfPicturedAsBroken(): void
    {
        AmpConfig::set('custom_logo', '', true);

        $html = (new MediaEmbedUnavailableView('https://example.org/albums.php?album=1'))->render();

        $this->assertStringNotContainsString('<img', $html, 'an empty logo would render as a broken image');
        $this->assertStringContainsString('Dogmazic', $html);
    }

    public function testARefusedFrameIsNotAMissingPage(): void
    {
        $this->assertSame(403, MediaEmbedUnavailableView::statusFor(true), 'the object is there, the player is not');
        $this->assertSame(404, MediaEmbedUnavailableView::statusFor(false));
    }

    public function testTheCardCarriesNothingAStrangerSPageCouldSuffer(): void
    {
        $html = (new MediaEmbedUnavailableView('https://example.org/albums.php?album=1'))->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<audio', $html, 'a switched off player publishes no stream');
        $this->assertStringNotContainsString('<link', $html, 'the page is framed, so it brings its own styles');
        $this->assertStringContainsString('noindex', $html);
    }

    public function testTheCardPointsAtThePageItCouldNotPlay(): void
    {
        AmpConfig::set('custom_logo', 'https://example.org/logo.svg', true);

        $html = (new MediaEmbedUnavailableView('https://example.org/albums.php?action=show&album=1'))->render();

        $this->assertStringContainsString('href="https://example.org/albums.php?action=show&amp;album=1"', $html);
        $this->assertStringContainsString('src="https://example.org/logo.svg"', $html);
    }

    public function testTheLinkIsEscapedBeforeItReachesTheFrame(): void
    {
        $html = (new MediaEmbedUnavailableView('https://example.org/"><script>alert(1)</script>'))->render();

        $this->assertStringNotContainsString('<script>alert(1)', $html);
    }

    protected function setUp(): void
    {
        foreach (['custom_logo', 'site_title', 'lang'] as $key) {
            $this->config[$key] = AmpConfig::get($key);
        }

        AmpConfig::set('site_title', 'Dogmazic', true);
    }

    protected function tearDown(): void
    {
        foreach ($this->config as $key => $value) {
            AmpConfig::set($key, $value, true);
        }
    }
}
