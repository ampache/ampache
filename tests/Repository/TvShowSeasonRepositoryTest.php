<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class TvShowSeasonRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private MockInterface $configContainer;

    private TvShowSeasonRepository $subject;

    public function setUp(): void
    {
        $this->database        = $this->mock(Connection::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new TvShowSeasonRepository(
            $this->database,
            $this->configContainer
        );
    }

    public function testCollectGarbageDeletes(): void
    {
        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `tvshow_season` USING `tvshow_season` LEFT JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` WHERE `tvshow_episode`.`id` IS NULL'
            )
            ->once();

        $this->subject->collectGarbage();
    }

    public function testGetEpisodeIdsReturnsList(): void
    {
        $tvshowId  = 666;
        $episodeId = 42;

        $result = $this->mock(Result::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CATALOG_DISABLE)
            ->once()
            ->andReturnTrue();

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `tvshow_episode`.`id` FROM `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` WHERE `tvshow_episode`.`season` = ? AND `catalog`.`enabled` = \'1\' ORDER BY `tvshow_episode`.`episode_number`',
                [$tvshowId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $episodeId, false);

        $this->assertSame(
            [$episodeId],
            $this->subject->getEpisodeIds($tvshowId)
        );
    }

    public function testGetExtraInfoReturnsEmptyArrayIfNothingWasFound(): void
    {
        $tvShowId = 666;

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count`, `video`.`catalog` as `catalog_id` FROM `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `tvshow_episode`.`season` = ? GROUP BY `catalog_id`',
                [$tvShowId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getExtraInfo($tvShowId)
        );
    }

    public function testGetExtraInfoReturnsData(): void
    {
        $tvShowId = 666;
        $result   = ['some-result'];

        $this->database->shouldReceive('fetchAssociative')
            ->with(
                'SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count`, `video`.`catalog` as `catalog_id` FROM `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `tvshow_episode`.`season` = ? GROUP BY `catalog_id`',
                [$tvShowId]
            )
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->getExtraInfo($tvShowId)
        );
    }

    public function testSetTvShowSets(): void
    {
        $tvShowId = 666;
        $seasonId = 42;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `tvshow_season` SET `tvshow` = ? WHERE `id` = ?',
                [$tvShowId, $seasonId]
            )
            ->once();

        $this->subject->setTvShow($tvShowId, $seasonId);
    }

    public function testFindByTvShowandSeasonNumberReturnsNullIfNothingWasFound(): void
    {
        $tvShowId     = 666;
        $seasonNumber = 42;

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `tvshow_season` WHERE `tvshow` = ? AND `season_number` = ? LIMIT 1',
                [$tvShowId, $seasonNumber]
            )
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->findByTvShowAndSeasonNumber($tvShowId, $seasonNumber)
        );
    }

    public function testFindByTvShowandSeasonNumberReturnsData(): void
    {
        $tvShowId     = 666;
        $seasonNumber = 42;
        $result       = 33;

        $this->database->shouldReceive('fetchOne')
            ->with(
                'SELECT `id` FROM `tvshow_season` WHERE `tvshow` = ? AND `season_number` = ? LIMIT 1',
                [$tvShowId, $seasonNumber]
            )
            ->once()
            ->andReturn((string) $result);

        $this->assertSame(
            $result,
            $this->subject->findByTvShowAndSeasonNumber($tvShowId, $seasonNumber)
        );
    }

    public function testAddSeasonReturnsData(): void
    {
        $tvShowId     = 666;
        $seasonNumber = 42;
        $result       = 33;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `tvshow_season` (`tvshow`, `season_number`) VALUES (?, ?)',
                [$tvShowId, $seasonNumber]
            )
            ->once();
        $this->database->shouldReceive('lastInsertId')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $result);

        $this->assertSame(
            $result,
            $this->subject->addSeason($tvShowId, $seasonNumber)
        );
    }

    public function testDeleteDeletes(): void
    {
        $seasonId = 666;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `tvshow_season` WHERE `id` = ?',
                [$seasonId]
            )
            ->once();

        $this->subject->delete($seasonId);
    }

    public function testUpdateUpdates(): void
    {
        $tvShowId     = 666;
        $seasonNumber = 42;
        $seasonId     = 33;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `tvshow_season` SET `season_number` = ?, `tvshow` = ? WHERE `id` = ?',
                [
                    $seasonNumber,
                    $tvShowId,
                    $seasonId
                ]
            )
            ->once();

        $this->subject->update($tvShowId, $seasonNumber, $seasonId);
    }

    public function testGetSeasonIdsByTvShowIdReturnsList(): void
    {
        $tvShowId = 666;
        $seasonId = 42;

        $result = $this->mock(Result::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `tvshow_season` WHERE `tvshow` = ? ORDER BY `season_number`',
                [$tvShowId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $seasonId, false);

        $this->assertSame(
            [$seasonId],
            $this->subject->getSeasonIdsByTvShowId($tvShowId)
        );
    }
}
