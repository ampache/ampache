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

namespace Ampache\Module\Api;

use Ampache\MockeryTestCase;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\SearchRepositoryInterface;
use Mockery\MockInterface;
use Override;
use ReflectionClass;

/**
 * How many playlists one answer carries when their songs are asked for.
 *
 * Every track of every playlist becomes an array held until the whole document is encoded, so a page counted
 * in playlists bounds nothing: a library of 264 295 entries needed 789M to answer on a server given 256M.
 */
class PlaylistTrackBudgetTest extends MockeryTestCase
{
    /** The calibration is free to move: every case here is written against whatever it currently is. */
    private int $budget;

    private PlaylistRepositoryInterface&MockInterface $playlistRepository;
    private SearchRepositoryInterface&MockInterface $searchRepository;
    private PlaylistTrackBudget $subject;

    public function testAListLongerThanTheBudgetTravelsOnItsOwn(): void
    {
        $this->given(playlists: [1 => $this->budget * 2, 2 => 10, 3 => 10]);

        $this->assertSame(1, $this->subject->limitFor([1, 2, 3], 0, 0), 'refusing it outright would page for ever');
    }

    public function testASmallLibraryIsNotPagedAtAll(): void
    {
        $this->given(playlists: [1 => 10, 2 => 20, 3 => 30]);

        $this->assertSame(3, $this->subject->limitFor([1, 2, 3], 0, 0));
    }

    public function testCountingStartsAtTheOffsetTheCallerAskedFor(): void
    {
        $this->given(playlists: [1 => $this->budget * 2, 2 => 10, 3 => 10]);

        $this->assertSame(2, $this->subject->limitFor([1, 2, 3], 1, 0), 'the huge one is behind the offset');
    }

    public function testSearchesAreCountedLikePlaylists(): void
    {
        $this->given(playlists: [1 => $this->budget - 1], searches: [7 => $this->budget - 1]);

        $this->assertSame(1, $this->subject->limitFor([1, 'smart_7'], 0, 0));
    }

    public function testTheCallersOwnLimitIsNeverExceeded(): void
    {
        $this->given(playlists: [1 => 10, 2 => 10, 3 => 10]);

        $this->assertSame(2, $this->subject->limitFor([1, 2, 3], 0, 2));
    }

    public function testThePageStopsBeforeTheBudgetRatherThanAfterIt(): void
    {
        $this->given(playlists: [1 => $this->budget - 1, 2 => $this->budget - 1, 3 => 10]);

        $this->assertSame(1, $this->subject->limitFor([1, 2, 3], 0, 0), 'adding the second would double the budget');
    }

    #[Override]
    protected function setUp(): void
    {
        $this->budget             = (int) (new ReflectionClass(PlaylistTrackBudget::class))->getConstant('TRACK_BUDGET');
        $this->playlistRepository = $this->mock(PlaylistRepositoryInterface::class);
        $this->searchRepository   = $this->mock(SearchRepositoryInterface::class);

        $this->subject = new PlaylistTrackBudget(
            $this->playlistRepository,
            $this->searchRepository
        );
    }

    /**
     * @param array<int, int> $playlists id => last_count
     * @param array<int, int> $searches id => last_count
     */
    private function given(array $playlists = [], array $searches = []): void
    {
        $rows = static function (array $counts): array {
            $out = [];
            foreach ($counts as $id => $count) {
                $out[] = ['id' => $id, 'last_count' => $count];
            }

            return $out;
        };

        $this->playlistRepository->shouldReceive('getRowsByIds')
            ->andReturnUsing(static fn(array $ids): array => $rows(array_intersect_key($playlists, array_flip($ids))));
        $this->searchRepository->shouldReceive('getRowsByIds')
            ->andReturnUsing(static fn(array $ids): array => $rows(array_intersect_key($searches, array_flip($ids))));
    }
}
