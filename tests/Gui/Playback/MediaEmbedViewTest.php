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
 * The embed is published into a stranger's page. `Stream::get_base_url()` puts the listener's session id in
 * the stream url whenever the instance requires a session, so on such an instance there is no url worth
 * publishing -- it would be useless to everyone else and would leak a session.
 */
class MediaEmbedViewTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $config = [];

    public function testAListAnnouncesMoreHeightThanASingleTrack(): void
    {
        $this->assertSame(MediaEmbedView::HEIGHT_SINGLE, MediaEmbedView::heightFor(1));
        $this->assertSame(MediaEmbedView::HEIGHT_SINGLE, MediaEmbedView::heightFor(0));
        $this->assertGreaterThan(MediaEmbedView::heightFor(1), MediaEmbedView::heightFor(2), 'a list needs room for the list');
    }

    public function testAnInstanceThatNeedsNoSessionCanBeEmbedded(): void
    {
        $this->given(useAuth: false, requireSession: false);
        $this->assertTrue(MediaEmbedView::isAvailable());

        $this->given(useAuth: true, requireSession: false);
        $this->assertTrue(MediaEmbedView::isAvailable(), 'the session id is only added when both are on');

        $this->given(useAuth: false, requireSession: true);
        $this->assertTrue(MediaEmbedView::isAvailable());
    }

    public function testAnInstanceThatRequiresASessionIsNotEmbeddable(): void
    {
        $this->given(useAuth: true, requireSession: true);

        $this->assertFalse(
            MediaEmbedView::isAvailable(),
            'the stream url would carry the session id of whoever rendered the page'
        );
    }

    protected function setUp(): void
    {
        foreach (['use_auth', 'require_session'] as $key) {
            $this->config[$key] = AmpConfig::get($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->config as $key => $value) {
            AmpConfig::set($key, $value, true);
        }
    }

    private function given(bool $useAuth, bool $requireSession): void
    {
        AmpConfig::set('use_auth', $useAuth, true);
        AmpConfig::set('require_session', $requireSession, true);
    }
}
