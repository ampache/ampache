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

namespace Ampache\Module\Statistics;

use Ampache\Module\Database\database_object;
use Ampache\Repository\RatingRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RatingCacheTest extends TestCase
{
    private ContainerInterface&MockObject $dic;
    private RatingRepositoryInterface&MockObject $repository;

    public function testAVisitorStillGetsTheAverageAndItsVoters(): void
    {
        // -1 is the identity a visitor rates under and it owns ratings like anybody else,
        // so the personal half is read for it too
        $this->repository->expects(static::once())
            ->method('getUserRatings')
            ->with('song', [1, 2], -1)
            ->willReturn([2 => 4]);
        $this->repository->expects(static::once())
            ->method('getAverageRatings')
            ->with('song', [1, 2])
            ->willReturn([1 => [4.14, 7]]);

        Rating::build_cache('song', [1, 2], -1);

        self::assertSame([4.14, 7], database_object::get_from_cache('rating_song_all', 1));
        self::assertSame(4.14, new Rating(1, 'song')->get_average_rating());
        self::assertSame(7, new Rating(1, 'song')->get_rating_count());
        self::assertSame(4, new Rating(2, 'song')->get_user_rating(-1));
    }

    public function testTheAbsenceOfARatingIsCachedSoNoRowAsksAgain(): void
    {
        // the zero has to be stored: without it the widget on every row queries a rating
        // that cannot exist, which is one query per row for as long as the list is
        $this->repository->method('getUserRatings')->willReturn([]);
        $this->repository->method('getAverageRatings')->willReturn([]);

        Rating::build_cache('album', [9], -1);

        self::assertTrue(database_object::is_cached('rating_album_user-1', 9));
        self::assertTrue(database_object::is_cached('rating_album_all', 9));
        self::assertNull(new Rating(9, 'album')->get_average_rating());
        self::assertSame(0, new Rating(9, 'album')->get_rating_count());
        self::assertNull(new Rating(9, 'album')->get_user_rating(-1));
    }

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RatingRepositoryInterface::class);
        $this->dic        = $this->createMock(ContainerInterface::class);
        $this->dic->method('get')->willReturn($this->repository);

        // `Rating` reaches its repository through the `global $dic` bridge; phpunit.xml sets
        // backupGlobals so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;

        database_object::clear_cache();
    }

    protected function tearDown(): void
    {
        database_object::clear_cache();
    }
}
