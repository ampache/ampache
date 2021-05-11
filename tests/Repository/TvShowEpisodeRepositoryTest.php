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
use Ampache\Repository\Model\TvShowEpisodeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class TvShowEpisodeRepositoryTest extends MockeryTestCase
{
    private MockInterface $database;

    private MockInterface $configContainer;

    private TvShowEpisodeRepository $subject;

    public function setUp(): void
    {
        $this->database        = $this->mock(Connection::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new TvShowEpisodeRepository(
            $this->database,
            $this->configContainer
        );
    }

    public function testGetEpisodeIdsByTvShowReturnsList(): void
    {
        $tvShowId  = 666;
        $episodeId = 42;

        $result = $this->mock(Result::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CATALOG_DISABLE)
            ->once()
            ->andReturnTrue();

        $this->database->shouldReceive('executeQuery')
            ->with(
                'SELECT `tvshow_episode`.`id` FROM `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` LEFT JOIN `tvshow_season` ON `tvshow_season`.`id` = `tvshow_episode`.`season` WHERE `tvshow_season`.`tvshow` = ? AND `catalog`.`enabled` = \'1\' ORDER BY `tvshow_season`.`season_number`, `tvshow_episode`.`episode_number`',
                [$tvShowId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->times(2)
            ->andReturn((string) $episodeId, false);

        $this->assertSame(
            [$episodeId],
            $this->subject->getEpisodeIdsByTvShow($tvShowId)
        );
    }

    public function testCollectGarbageDeletes(): void
    {
        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `tvshow_episode` USING `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `video`.`id` IS NULL'
            )
            ->once();

        $this->subject->collectGarbage();
    }

    public function testDeleteDeletes(): void
    {
        $episodeId = 666;

        $episode = $this->mock(TvShowEpisodeInterface::class);

        $this->database->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `tvshow_episode` WHERE `id` = ?',
                [$episodeId]
            )
            ->once();

        $episode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($episodeId);

        $this->subject->delete($episode);
    }

    public function testUpdateUpdates(): void
    {
        $originalName  = 'some-name';
        $seasonId      = 42;
        $episodeNumber = 666;
        $summary       = 'some-summary';
        $episodeId     = 33;

        $this->database->shouldReceive('executeQuery')
            ->with(
                'UPDATE `tvshow_episode` SET `original_name` = ?, `season` = ?, `episode_number` = ?, `summary` = ? WHERE `id` = ?',
                [$originalName, $seasonId, $episodeNumber, $summary, $episodeId]
            )
            ->once();

        $this->subject->update(
            $originalName,
            $seasonId,
            $episodeNumber,
            $summary,
            $episodeId
        );
    }

    public function testCreateCreates(): void
    {
        $episodeId     = 666;
        $originalName  = 'some-name';
        $seasonId      = 42;
        $episodeNumber = 33;
        $summary       = 'some-summary';

        $this->database->shouldReceive('executeQuery')
            ->with(
                'INSERT INTO `tvshow_episode` (`id`, `original_name`, `season`, `episode_number`, `summary`) VALUES (?, ?, ?, ?, ?)',
                [
                    $episodeId,
                    $originalName,
                    $seasonId,
                    $episodeNumber,
                    $summary
                ]
            )
            ->once();

        $this->subject->create(
            $episodeId,
            $originalName,
            $seasonId,
            $episodeNumber,
            $summary
        );
    }
}
