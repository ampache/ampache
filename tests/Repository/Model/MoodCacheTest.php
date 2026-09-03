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
use Ampache\Repository\MoodRepositoryInterface;
use DI\Container;
use Override;

class MoodCacheTest extends MockeryTestCase
{
    private ?object $previousDic = null;
    private object $repository;

    public function testAColdObjectStillReadsTheRepository(): void
    {
        $this->repository->shouldReceive('getTopMoods')
            ->with('song', 3, 0)
            ->once()
            ->andReturn([]);

        self::assertSame([], Mood::get_top_moods('song', 3, 0));
    }

    public function testAWarmPageIsNotReadAgainOneByOne(): void
    {
        $this->repository->shouldReceive('getTopMoodsBulk')
            ->with('song', [1, 2])
            ->once()
            ->andReturn([1 => [['id' => 7, 'name' => 'calm', 'user' => 0, 'count' => 3]], 2 => []]);
        $this->repository->shouldNotReceive('getTopMoods');

        Mood::build_object_mood_cache('song', [1, 2]);

        self::assertSame([['id' => 7, 'name' => 'calm', 'user' => 0, 'count' => 3]], Mood::get_top_moods('song', 1, 0));
        self::assertSame([], Mood::get_top_moods('song', 2, 0));
    }

    public function testRemoveMapForgetsTheObject(): void
    {
        Mood::add_to_cache('object_moods_song', 42, [['id' => 7, 'name' => 'calm', 'user' => 0, 'count' => 3]]);
        Mood::add_to_cache('object_moods_warm_song', 42, [true]);

        $mood = new Mood(0);
        $mood->remove_map('song', 42);

        self::assertFalse(Mood::is_cached('object_moods_song', 42));
        self::assertFalse(Mood::is_cached('object_moods_warm_song', 42));
    }

    public function testTheLimitIsAppliedOnTheWarmList(): void
    {
        $this->repository->shouldReceive('getTopMoodsBulk')
            ->andReturn([1 => [['id' => 7, 'name' => 'calm', 'user' => 0, 'count' => 3], ['id' => 8, 'name' => 'dark', 'user' => 0, 'count' => 1]]]);

        Mood::build_object_mood_cache('song', [1]);

        self::assertCount(1, Mood::get_top_moods('song', 1, 1));
    }

    #[Override]
    protected function setUp(): void
    {
        global $dic;

        $this->previousDic = $dic;

        $repository = $this->mock(MoodRepositoryInterface::class);
        $repository->shouldIgnoreMissing();
        $this->repository = $repository;

        $container = $this->mock(Container::class);
        $container->shouldReceive('get')
            ->with(MoodRepositoryInterface::class)
            ->andReturn($repository);

        $dic = $container;

        Mood::clear_cache();
    }

    #[Override]
    protected function tearDown(): void
    {
        global $dic;

        $dic = $this->previousDic;

        Mood::clear_cache();

        parent::tearDown();
    }
}
