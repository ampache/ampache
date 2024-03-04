<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Repository;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Podcast\PodcastEpisodeStateEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use DateTime;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class PodcastEpisodeRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private ModelFactoryInterface&MockObject $modelFactory;

    private DatabaseConnectionInterface&MockObject $connection;

    private ConfigContainerInterface&MockObject $configContainer;

    private PodcastEpisodeRepository $subject;

    protected function setUp(): void
    {
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);
        $this->connection      = $this->createMock(DatabaseConnectionInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new PodcastEpisodeRepository(
            $this->modelFactory,
            $this->connection,
            $this->configContainer,
        );
    }

    public function testGetEpisodesReturnsEpisodesWithoutStateFilterAndDisabledCatalogs(): void
    {
        $podcast = $this->createMock(Podcast::class);
        $result  = $this->createMock(PDOStatement::class);

        $podcastId = 666;
        $episodeId = 42;

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::CATALOG_DISABLE)
            ->willReturn('1');

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `podcast_episode`.`id` FROM `podcast_episode` LEFT JOIN `catalog` ON `catalog`.`id` = `podcast_episode`.`catalog` WHERE `podcast_episode`.`podcast` = ? AND `catalog`.`enabled` = \'1\' ORDER BY `podcast_episode`.`pubdate` DESC',
                [$podcastId],
            )
            ->willReturn($result);

        $podcast->expects(static::once())
            ->method('getId')
            ->willReturn($podcastId);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $episodeId, false);

        static::assertSame(
            [$episodeId],
            $this->subject->getEpisodes($podcast),
        );
    }

    public function testGetEpisodesReturnsEpisodesWithStateFilter(): void
    {
        $podcast = $this->createMock(Podcast::class);
        $result  = $this->createMock(PDOStatement::class);

        $stateFilter = PodcastEpisodeStateEnum::COMPLETED;
        $podcastId   = 666;
        $episodeId   = 42;

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::CATALOG_DISABLE)
            ->willReturn('');

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `podcast_episode`.`id` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ? AND `podcast_episode`.`state` = ? ORDER BY `podcast_episode`.`pubdate` DESC',
                [$podcastId, $stateFilter->value],
            )
            ->willReturn($result);

        $podcast->expects(static::once())
            ->method('getId')
            ->willReturn($podcastId);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $episodeId, false);

        static::assertSame(
            [$episodeId],
            $this->subject->getEpisodes($podcast, $stateFilter),
        );
    }

    public function testDeleteEpisodeDeletes(): void
    {
        $episode = $this->createMock(Podcast_Episode::class);

        $episodeId    = 666;
        $replaceQuery = <<<SQL
        REPLACE INTO
            `deleted_podcast_episode`
            (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`, `podcast`)
        SELECT
            `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip`, `podcast`
        FROM
            `podcast_episode`
        WHERE
            `id` = ?;
        SQL;
        $deleteQuery = 'DELETE FROM `podcast_episode` WHERE `id` = ?';

        $episode->expects(static::once())
            ->method('getId')
            ->willReturn($episodeId);

        $this->connection->expects(self::exactly(2))
            ->method('query')
            ->with(...self::withConsecutive(
                [$replaceQuery, [$episodeId]],
                [$deleteQuery, [$episodeId]],
            ));

        $this->subject->deleteEpisode($episode);
    }

    public function testGetEpisodeEligibleForDeletionReturnsNothingIfDisabled(): void
    {
        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_KEEP)
            ->willReturn('');

        static::assertSame(
            [],
            iterator_to_array($this->subject->getEpisodesEligibleForDeletion($this->createMock(Podcast::class)))
        );
    }

    public function testGetEpisodeEligibleForDeletionYieldsEpisodes(): void
    {
        $keepLimit = 666;
        $episodeId = 42;
        $podcastId = 21;

        $podcast = $this->createMock(Podcast::class);
        $result  = $this->createMock(PDOStatement::class);
        $episode = $this->createMock(Podcast_Episode::class);

        $podcast->expects(static::once())
            ->method('getId')
            ->willReturn($podcastId);

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_KEEP)
            ->willReturn((string) $keepLimit);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                sprintf(
                    'SELECT `id` FROM `podcast_episode` WHERE `podcast` = ? ORDER BY `pubdate` DESC LIMIT %d,18446744073709551615',
                    $keepLimit
                ),
                [$podcastId]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $episodeId, false);

        $this->modelFactory->expects(static::once())
            ->method('createPodcastEpisode')
            ->with($episodeId)
            ->willReturn($episode);

        static::assertSame(
            [$episode],
            iterator_to_array($this->subject->getEpisodesEligibleForDeletion($podcast))
        );
    }

    public function testGetEpisodeEligibleForDownloadYieldsEpisodesWithoutLimit(): void
    {
        $episodeId     = 42;
        $podcastId     = 21;
        $lastSyncDate  = new DateTime();

        $podcast = $this->createMock(Podcast::class);
        $result  = $this->createMock(PDOStatement::class);
        $episode = $this->createMock(Podcast_Episode::class);

        $query = <<<SQL
            SELECT
                `id`
            FROM
                `podcast_episode`
            WHERE
                `podcast` = ?
                AND
                (`addition_time` > ? OR `state` = ?)
            ORDER BY
                `pubdate`
            DESC%s
            SQL;

        $podcast->expects(static::once())
            ->method('getId')
            ->willReturn($podcastId);
        $podcast->expects(static::once())
            ->method('getLastSyncDate')
            ->willReturn($lastSyncDate);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                sprintf(
                    $query,
                    ''
                ),
                [
                    $podcastId,
                    $lastSyncDate->getTimestamp(),
                    PodcastEpisodeStateEnum::PENDING->value
                ]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $episodeId, false);

        $this->modelFactory->expects(static::once())
            ->method('createPodcastEpisode')
            ->with($episodeId)
            ->willReturn($episode);

        static::assertSame(
            [$episode],
            iterator_to_array($this->subject->getEpisodesEligibleForDownload($podcast, null))
        );
    }

    public function testGetEpisodeEligibleForDownloadYieldsEpisodes(): void
    {
        $downloadLimit = 666;
        $episodeId     = 42;
        $podcastId     = 21;
        $lastSyncDate  = new DateTime();

        $podcast = $this->createMock(Podcast::class);
        $result  = $this->createMock(PDOStatement::class);
        $episode = $this->createMock(Podcast_Episode::class);

        $query = <<<SQL
            SELECT
                `id`
            FROM
                `podcast_episode`
            WHERE
                `podcast` = ?
                AND
                (`addition_time` > ? OR `state` = ?)
            ORDER BY
                `pubdate`
            DESC%s
            SQL;

        $podcast->expects(static::once())
            ->method('getId')
            ->willReturn($podcastId);
        $podcast->expects(static::once())
            ->method('getLastSyncDate')
            ->willReturn($lastSyncDate);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                sprintf(
                    $query,
                    sprintf(' LIMIT %d', $downloadLimit)
                ),
                [
                    $podcastId,
                    $lastSyncDate->getTimestamp(),
                    PodcastEpisodeStateEnum::PENDING->value
                ]
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $episodeId, false);

        $this->modelFactory->expects(static::once())
            ->method('createPodcastEpisode')
            ->with($episodeId)
            ->willReturn($episode);

        static::assertSame(
            [$episode],
            iterator_to_array($this->subject->getEpisodesEligibleForDownload($podcast, $downloadLimit))
        );
    }

    public function testGetEpisodeCountReturnsValue(): void
    {
        $podcastId = 666;
        $result    = 42;

        $podcast = $this->createMock(Podcast::class);

        $podcast->expects(static::once())
            ->method('getId')
            ->willReturn($podcastId);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT COUNT(id) from `podcast_episode` where `podcast` = ?',
                [$podcastId]
            )
            ->willReturn((string) $result);

        static::assertSame(
            $result,
            $this->subject->getEpisodeCount($podcast)
        );
    }

    public function testUpdateStateUpdates(): void
    {
        $episode = $this->createMock(Podcast_Episode::class);

        $state     = 'some-state';
        $episodeId = 666;

        $episode->expects(static::once())
            ->method('getId')
            ->willReturn($episodeId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `podcast_episode` SET `state` = ? WHERE `id` = ?',
                [$state, $episodeId]
            );

        $this->subject->updateState($episode, $state);
    }

    public function testCollectGarbageCollects(): void
    {
        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `podcast_episode` USING `podcast_episode` LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` WHERE `podcast`.`id` IS NULL'
            );

        $this->subject->collectGarbage();
    }
}
