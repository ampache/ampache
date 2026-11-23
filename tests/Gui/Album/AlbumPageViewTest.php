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

namespace Ampache\Gui\Album;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\TestCase;

class AlbumPageViewTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $config = [];

    // the line is offered to whoever is looking at the page, not to whoever may use the interface
    public function testTheShareLineIsForTheVisitorOfThePage(): void
    {
        $guest         = $this->createMock(User::class);
        $guest->access = AccessLevelEnum::GUEST->value;

        self::assertTrue($this->view($guest)->showShare());
        self::assertFalse($this->view(null)->showShare());
    }

    protected function setUp(): void
    {
        foreach (['share', 'share_line_access_level'] as $key) {
            $this->config[$key] = AmpConfig::get($key);
        }

        AmpConfig::set('share', true, true);
        AmpConfig::set('share_line_access_level', AccessLevelEnum::GUEST->value, true);
    }

    protected function tearDown(): void
    {
        foreach ($this->config as $key => $value) {
            AmpConfig::set($key, $value, true);
        }
    }

    private function view(?User $user): AlbumPageView
    {
        return new AlbumPageView(
            $this->createMock(Album::class),
            $this->createMock(BrowseFactoryInterface::class),
            $user,
            '/amp',
            false,
            false,
            false,
            false
        );
    }
}
