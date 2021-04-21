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
use Ampache\Module\Podcast\PodcastStateEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\Model\PodcastInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class PodcastEpisodeRepositoryTest extends MockeryTestCase
{
    /** @var MockInterface|ConfigContainerInterface */
    private MockInterface $configContainer;

    /** @var MockInterface|Connection */
    private MockInterface $connection;

    /** @var MockInterface|ModelFactoryInterface */
    private MockInterface $modelFactory;

    private PodcastEpisodeRepository $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->connection      = $this->mock(Connection::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new PodcastEpisodeRepository(
            $this->configContainer,
            $this->connection,
            $this->modelFactory
        );
    }

    public function testGetNewestPodcastsIdsReturnsValue(): void
    {
        $catalogId = 666;
        $count     = 42;
        $episodeId = 33;

        $episode = $this->mock(PodcastEpisodeInterface::class);
        $result  = $this->mock(Result::class);

        $sql = <<<SQL
        SELECT
            `podcast_episode`.`id`
        FROM
            `podcast_episode`
        INNER JOIN
            `podcast`
        ON
            `podcast`.`id` = `podcast_episode`.`podcast`
        WHERE
            `podcast`.`catalog` = ? 
        ORDER BY
            `podcast_episode`.`pubdate` DESC
        SQL;

        $sql .= sprintf(' LIMIT %d', $count);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                [$catalogId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $episodeId, false);

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($episodeId)
            ->once()
            ->andReturn($episode);

        $episode->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        foreach ($this->subject->getNewestPodcastEpisodes($catalogId, $count) as $episodeObj) {
            $this->assertSame(
                $episode,
                $episodeObj
            );
        }
    }

    public function testGetDownloadableEpisodesReturnsData(): void
    {
        $podcast = $this->mock(PodcastInterface::class);
        $result  = $this->mock(Result::class);
        $episode = $this->mock(PodcastEpisodeInterface::class);

        $episodeId = 666;
        $limit     = 42;
        $podcastId = 33;

        $sql = <<<SQL
        SELECT
            `podcast_episode`.`id`
        FROM
            `podcast_episode`
            INNER JOIN 
                `podcast`
            ON
                `podcast`.`id` = `podcast_episode`.`podcast`
        WHERE
            `podcast`.`id` = ? AND `podcast_episode`.`addition_time` > `podcast`.`lastsync`
        ORDER BY
              `podcast_episode`.`pubdate` DESC
        LIMIT %d;
        SQL;

        $podcast->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastId);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                sprintf($sql, $limit),
                [$podcastId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $episodeId, false);

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($episodeId)
            ->once()
            ->andReturn($episode);

        $episode->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        foreach ($this->subject->getDownloadableEpisodes($podcast, $limit) as $episodeObj) {
            $this->assertSame(
                $episode,
                $episodeObj
            );
        }
    }

    public function testGetDeletableEpisodesReturnsData(): void
    {
        $podcast = $this->mock(PodcastInterface::class);
        $episode = $this->mock(PodcastEpisodeInterface::class);
        $result  = $this->mock(Result::class);

        $podcastId = 666;
        $episodeId = 42;
        $limit     = 33;

        $sql = <<<SQL
        SELECT
            `podcast_episode`.`id`
        FROM
            `podcast_episode`
        WHERE
            `podcast_episode`.`podcast` = ?
        ORDER BY
            `podcast_episode`.`pubdate` DESC
        LIMIT
            %d,18446744073709551615
        SQL;

        $podcast->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastId);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                sprintf($sql, $limit),
                [$podcastId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $episodeId, false);

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($episodeId)
            ->once()
            ->andReturn($episode);

        $episode->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        foreach ($this->subject->getDeletableEpisodes($podcast, $limit) as $episodeObj) {
            $this->assertSame(
                $episode,
                $episodeObj
            );
        }
    }

    public function testCreateAddsAndReturnsTrueIfSuccessful(): void
    {
        $podcastId       = 666;
        $title           = 'some-title';
        $guid            = 'some-guid';
        $source          = 'some-source';
        $website         = 'some-website';
        $description     = 'some-description';
        $author          = 'some-author';
        $category        = 'some-category';
        $time            = 42;
        $publicationDate = 33;

        $result  = $this->mock(Result::class);
        $podcast = $this->mock(PodcastInterface::class);

        $podcast->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastId);

        $sql = <<<SQL
        INSERT INTO
            `podcast_episode`
            (`title`, `guid`, `podcast`, `state`, `source`, `website`, `description`, `author`, `category`, `time`, `pubdate`, `addition_time`)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())
        SQL;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                [
                    $title,
                    $guid,
                    $podcastId,
                    PodcastStateEnum::PENDING,
                    $source,
                    $website,
                    $description,
                    $author,
                    $category,
                    $time,
                    $publicationDate,
                ]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(1);

        $this->assertTrue(
            $this->subject->create(
                $podcast,
                $title,
                $guid,
                $source,
                $website,
                $description,
                $author,
                $category,
                $time,
                $publicationDate
            )
        );
    }

    public function testGetEpisodeIdsReturnsData(): void
    {
        $podcast = $this->mock(PodcastInterface::class);
        $result  = $this->mock(Result::class);

        $episodeId = 666;
        $podcastId = 42;
        $state     = 'some-state';

        $sql = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` LEFT JOIN `catalog` ON `catalog`.`id` = `podcast`.`catalog` WHERE `podcast_episode`.`podcast`= ? AND `podcast_episode`.`state` = ? AND `catalog`.`enabled` = \'1\' ORDER BY `podcast_episode`.`pubdate` DESC';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::CATALOG_DISABLE)
            ->once()
            ->andReturnTrue();

        $podcast->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastId);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                [$podcastId, $state]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $episodeId, false);

        $this->assertSame(
            [$episodeId],
            $this->subject->getEpisodeIds($podcast, $state)
        );
    }

    public function testRemoveReturnsTrueIfSuccessFul(): void
    {
        $episode = $this->mock(PodcastEpisodeInterface::class);
        $result  = $this->mock(Result::class);

        $episodeId = 666;

        $episode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($episodeId);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `podcast_episode` WHERE `id` = ?',
                [$episodeId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(1);

        $this->assertTrue(
            $this->subject->remove($episode)
        );
    }

    public function testChangeStateUpdates(): void
    {
        $episode = $this->mock(PodcastEpisodeInterface::class);

        $episodeId = 666;
        $state     = 'some-state';

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE `podcast_episode` SET `state` = ? WHERE `id` = ?',
                [$state, $episodeId]
            )
            ->once();

        $episode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($episodeId);

        $this->subject->changeState(
            $episode,
            $state
        );
    }

    public function testUpdateDownloadStateSetsData(): void
    {
        $episode = $this->mock(PodcastEpisodeInterface::class);

        $filePath  = 'some-path';
        $size      = 666;
        $duration  = 42;
        $episodeId = 33;
        $bitrate   = 21;
        $rate      = 123;
        $mode      = 'some-mode';

        $episode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($episodeId);

        $sql = <<<SQL
        UPDATE
            `podcast_episode`
        SET
            `file` = ?, `size` = ?, `time` = ?, `state` = ?, `bitrate` = ?, `rate` = ?, `mode` = ? WHERE `id` = ?
        SQL;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                [
                    $filePath,
                    $size,
                    $duration,
                    PodcastStateEnum::COMPLETED,
                    $bitrate,
                    $rate,
                    $mode,
                    $episodeId
                ]
            )
            ->once();

        $this->subject->updateDownloadState(
            $episode,
            $filePath,
            $size,
            $duration,
            $bitrate,
            $rate,
            $mode
        );
    }

    public function testCollectGarbageDeletesGarbage(): void
    {
        $sql = <<<SQL
        DELETE FROM
            `podcast_episode`
        USING
            `podcast_episode`
        LEFT JOIN
            `podcast`
        ON
            `podcast`.`id` = `podcast_episode`.`podcast`
        WHERE
            `podcast`.`id` IS NULL
        SQL;

        $this->connection->shouldReceive('executeQuery')
            ->with($sql)
            ->once();

        $this->subject->collectGarbage();
    }

    public function testGetEpisodeCountReturnsData(): void
    {
        $podcast = $this->mock(PodcastInterface::class);

        $podcastId = 666;
        $count     = 42;

        $sql = <<<SQL
        SELECT
            COUNT(`podcast_episode`.`id`) AS `episode_count`
        FROM
            `podcast_episode`
        WHERE
            `podcast_episode`.`podcast` = ?
        SQL;

        $podcast->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastId);

        $this->connection->shouldReceive('fetchOne')
            ->with($sql, [$podcastId])
            ->once()
            ->andReturn((string) $count);

        $this->assertSame(
            $count,
            $this->subject->getEpisodeCount($podcast)
        );
    }

    public function testGetFindByIdReturnsNullIfNotFound(): void
    {
        $id = 666;

        $episode = $this->mock(PodcastEpisodeInterface::class);

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($id)
            ->once()
            ->andReturn($episode);

        $episode->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->assertNull(
            $this->subject->findById($id)
        );
    }

    public function testUpdateUpdates(): void
    {
        $episode = $this->mock(PodcastEpisodeInterface::class);

        $title       = 'some-title';
        $website     = 'some-website';
        $description = 'some-description';
        $author      = 'some-author';
        $category    = 'some-category';
        $episodeId   = 666;

        $episode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($episodeId);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE `podcast_episode` SET `title` = ?, `website` = ?, `description` = ?, `author` = ?, `category` = ? WHERE `id` = ?',
                [$title, $website, $description, $author, $category, $episodeId]
            )
            ->once();

        $this->subject->update(
            $episode,
            $title,
            $website,
            $description,
            $author,
            $category
        );
    }

    public function testSetPlayedSetsState(): void
    {
        $episode = $this->mock(PodcastEpisodeInterface::class);

        $episodeId = 666;

        $episode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($episodeId);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE `podcast_episode` SET `played` = ? WHERE `id` = ?',
                [1, $episodeId]
            )
            ->once();

        $this->subject->setPlayed($episode);
    }
}
