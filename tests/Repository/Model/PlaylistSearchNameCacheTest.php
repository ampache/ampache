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

namespace Ampache\Repository\Model;

use Ampache\MockeryTestCase;
use Ampache\Repository\PlaylistRepositoryInterface;
use DI\Container;
use Override;

/**
 * `has_search()` runs for every row of a playlist page and the name lists it compares against are the same for
 * all of them, so they have to be read once rather than once per row.
 */
class PlaylistSearchNameCacheTest extends MockeryTestCase
{
    private ?object $previousDic = null;

    public function testADifferentOwnerGetsItsOwnEntry(): void
    {
        // the owned half of the lists depends on the playlist owner, so two owners cannot share one entry
        $repository = $this->mock(PlaylistRepositoryInterface::class);
        $repository->shouldReceive('findSearchNames')
            ->with(7, true)
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('findSearchNames')
            ->with(8, true)
            ->once()
            ->andReturn([21 => 'Some name']);
        $repository->shouldReceive('findSearchNames')
            ->with(0, false)
            ->twice()
            ->andReturn([]);

        $this->setDic($repository);

        $playlist       = new Playlist(0);
        $playlist->name = 'Some name';

        self::assertSame(0, $playlist->has_search(7));
        self::assertSame(21, $playlist->has_search(8));
    }

    public function testTheNameListsAreReadOncePerUserPair(): void
    {
        $repository = $this->mock(PlaylistRepositoryInterface::class);
        $repository->shouldReceive('findSearchNames')
            ->with(7, true)
            ->once()
            ->andReturn([]);
        $repository->shouldReceive('findSearchNames')
            ->with(0, false)
            ->once()
            ->andReturn([13 => 'Some name']);

        $this->setDic($repository);

        $playlist       = new Playlist(0);
        $playlist->name = 'Some name';

        self::assertSame(13, $playlist->has_search(7));
        self::assertSame(13, $playlist->has_search(7));
    }

    #[Override]
    protected function setUp(): void
    {
        global $dic;

        $this->previousDic = $dic;

        Playlist::clear_cache();
    }

    #[Override]
    protected function tearDown(): void
    {
        global $dic;

        $dic = $this->previousDic;

        Playlist::clear_cache();

        parent::tearDown();
    }

    private function setDic(object $repository): void
    {
        global $dic;

        $container = $this->mock(Container::class);
        $container->shouldReceive('get')
            ->with(PlaylistRepositoryInterface::class)
            ->andReturn($repository);

        $dic = $container;
    }
}
