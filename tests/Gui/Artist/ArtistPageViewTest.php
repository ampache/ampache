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

namespace Ampache\Gui\Artist;

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\Artist;
use PHPUnit\Framework\TestCase;

class ArtistPageViewTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $config = [];

    public function testTheShareLineGoesWithTheFeature(): void
    {
        AmpConfig::set('use_auth', false, true);
        AmpConfig::set('share', false, true);

        self::assertFalse($this->view(mayUse: true)->showShare());
    }

    public function testTheShareLineIsForAnAccountBehindLogins(): void
    {
        AmpConfig::set('use_auth', true, true);

        self::assertTrue($this->view(mayUse: true)->showShare());
        self::assertFalse($this->view(mayUse: false)->showShare());
    }

    public function testTheShareLineIsForEveryVisitorWhenThereAreNoLogins(): void
    {
        AmpConfig::set('use_auth', false, true);

        self::assertTrue($this->view(mayUse: false)->showShare());
    }

    protected function setUp(): void
    {
        foreach (['use_auth', 'share'] as $key) {
            $this->config[$key] = AmpConfig::get($key);
        }

        AmpConfig::set('share', true, true);
    }

    protected function tearDown(): void
    {
        foreach ($this->config as $key => $value) {
            AmpConfig::set($key, $value, true);
        }
    }

    private function view(bool $mayUse): ArtistPageView
    {
        return new ArtistPageView(
            $this->createMock(Artist::class),
            [],
            'album',
            $this->createMock(BrowseFactoryInterface::class),
            null,
            '/amp',
            false,
            false,
            $mayUse,
            false
        );
    }
}
