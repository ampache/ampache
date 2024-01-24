<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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
 *
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
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class PodcastRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private ModelFactoryInterface&MockObject $modelFactory;

    private DatabaseConnectionInterface&MockObject $connection;

    private ConfigContainerInterface&MockObject $configContainer;

    private PodcastRepository $subject;

    protected function setUp(): void
    {
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);
        $this->connection      = $this->createMock(DatabaseConnectionInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new PodcastRepository(
            $this->modelFactory,
            $this->connection,
            $this->configContainer,
        );
    }

    public function testFindAllReturnsAllItems(): void
    {
        $result  = $this->createMock(PDOStatement::class);
        $podcast = $this->createMock(Podcast::class);

        $podcastId = 666;

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'SELECT `id` FROM `podcast`',
            )
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetchColumn')
            ->willReturn((string) $podcastId, false);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with($podcastId)
            ->willReturn($podcast);

        static::assertSame(
            [$podcast],
            iterator_to_array($this->subject->findAll())
        );
    }

    public function testFindByFeedUrlReturnsNullIfNothingWasFound(): void
    {
        $feedUrl = 'some-url';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `podcast` WHERE `feed` = ?',
                [
                    $feedUrl,
                ],
            )
            ->willReturn(false);

        static::assertNull(
            $this->subject->findByFeedUrl($feedUrl),
        );
    }

    public function testFindByFeedUrlReturnsFoundPodcast(): void
    {
        $feedUrl   = 'some-url';
        $podcastId = 666;

        $podcast = $this->createMock(Podcast::class);

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `podcast` WHERE `feed` = ?',
                [
                    $feedUrl,
                ],
            )
            ->willReturn((string) $podcastId);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with($podcastId)
            ->willReturn($podcast);

        static::assertSame(
            $podcast,
            $this->subject->findByFeedUrl($feedUrl),
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
                [$podcastId, $stateFilter],
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

    public function testGetDeletedEpisodesReturnsData(): void
    {
        $result = $this->createMock(PDOStatement::class);

        $id           = 666;
        $additionTime = 123;
        $deleteTime   = 456;
        $title        = 'some-title';
        $file         = 'some-file';
        $catalog      = 789;
        $totalCount   = 111;
        $totalSkip    = 222;
        $podcast      = 333;

        $this->connection->expects(static::once())
            ->method('query')
            ->with('SELECT * FROM `deleted_podcast_episode`')
            ->willReturn($result);

        $result->expects(static::exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(
                [
                    'id' => (string) $id,
                    'addition_time' => (string) $additionTime,
                    'delete_time' => (string) $deleteTime,
                    'title' => $title,
                    'file' => $file,
                    'catalog' => (string) $catalog,
                    'total_count' => (string) $totalCount,
                    'total_skip' => (string) $totalSkip,
                    'podcast' => (string) $podcast,
                ],
                false
            );

        static::assertSame(
            [[
                'id' => $id,
                'addition_time' => $additionTime,
                'delete_time' => $deleteTime,
                'title' => $title,
                'file' => $file,
                'catalog' => $catalog,
                'total_count' => $totalCount,
                'total_skip' => $totalSkip,
                'podcast' => $podcast,
            ]],
            $this->subject->getDeletedEpisodes()
        );
    }

    public function testDeleteDeletesPodcast(): void
    {
        $podcastId = 666;

        $podcast = $this->createMock(Podcast::class);

        $podcast->expects(static::once())
            ->method('getId')
            ->willReturn($podcastId);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'DELETE FROM `podcast` WHERE `id` = ?',
                [$podcastId]
            );

        $this->subject->delete($podcast);
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
            DESC LIMIT %d
            SQL;

        $podcast->expects(static::once())
            ->method('getId')
            ->willReturn($podcastId);
        $podcast->expects(static::once())
            ->method('getLastSyncDate')
            ->willReturn($lastSyncDate);

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->willReturn((string) $downloadLimit);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                sprintf(
                    $query,
                    $downloadLimit
                ),
                [
                    $podcastId,
                    $lastSyncDate->getTimestamp(),
                    PodcastEpisodeStateEnum::PENDING
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
            iterator_to_array($this->subject->getEpisodesEligibleForDownload($podcast))
        );
    }

    public function testGetEpisodeEligibleForDownloadReturnsNothingIfDisabled(): void
    {
        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD)
            ->willReturn('');

        static::assertSame(
            [],
            iterator_to_array($this->subject->getEpisodesEligibleForDownload($this->createMock(Podcast::class)))
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

    public function testPersistCreateItem(): void
    {
        $catalogId     = 666;
        $feed          = 'some-feed';
        $title         = 'some-title';
        $website       = 'some-website';
        $description   = 'some-description';
        $language      = 'some-language';
        $generator     = 'some-generator';
        $copyright     = 'some-copyright';
        $totalSkip     = 123;
        $totalCount    = 456;
        $episodeCount  = 789;
        $lastBuildDate = new DateTime();
        $lastSyncDate  = new DateTime();
        $podcastId     = 42;

        $podcast = $this->createMock(Podcast::class);

        $podcast->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'INSERT INTO `podcast` (`catalog`, `feed`, `title`, `website`, `description`, `language`, `generator`, `copyright`, `total_skip`, `total_count`, `episodes`, `lastbuilddate`, `lastsync`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $catalogId,
                    $feed,
                    $title,
                    $website,
                    $description,
                    $language,
                    $generator,
                    $copyright,
                    $totalSkip,
                    $totalCount,
                    $episodeCount,
                    $lastBuildDate->getTimestamp(),
                    $lastSyncDate->getTimestamp()
                ]
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($podcastId);

        $podcast->expects(static::once())
            ->method('getCatalogId')
            ->willReturn($catalogId);
        $podcast->expects(static::once())
            ->method('getFeedUrl')
            ->willReturn($feed);
        $podcast->expects(static::once())
            ->method('getTitle')
            ->willReturn($title);
        $podcast->expects(static::once())
            ->method('getWebsite')
            ->willReturn($website);
        $podcast->expects(static::once())
            ->method('getDescription')
            ->willReturn($description);
        $podcast->expects(static::once())
            ->method('getLanguage')
            ->willReturn($language);
        $podcast->expects(static::once())
            ->method('getGenerator')
            ->willReturn($generator);
        $podcast->expects(static::once())
            ->method('getCopyright')
            ->willReturn($copyright);
        $podcast->expects(static::once())
            ->method('getTotalSkip')
            ->willReturn($totalSkip);
        $podcast->expects(static::once())
            ->method('getTotalCount')
            ->willReturn($totalCount);
        $podcast->expects(static::once())
            ->method('getEpisodeCount')
            ->willReturn($episodeCount);
        $podcast->expects(static::once())
            ->method('getLastBuildDate')
            ->willReturn($lastBuildDate);
        $podcast->expects(static::once())
            ->method('getLastSyncDate')
            ->willReturn($lastSyncDate);

        static::assertSame(
            $podcastId,
            $this->subject->persist($podcast)
        );
    }

    public function testPersistUpdatesItemIfNotNew(): void
    {
        $feed          = 'some-feed';
        $title         = 'some-title';
        $website       = 'some-website';
        $description   = 'some-description';
        $language      = 'some-language';
        $generator     = 'some-generator';
        $copyright     = 'some-copyright';
        $totalSkip     = 123;
        $totalCount    = 456;
        $episodeCount  = 789;
        $lastBuildDate = new DateTime();
        $lastSyncDate  = new DateTime();
        $podcastId     = 42;

        $podcast = $this->createMock(Podcast::class);

        $podcast->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `language` = ?, `generator` = ?, `copyright` = ?, `total_skip` = ?, `total_count` = ?, `episodes` = ?, `lastbuilddate` = ?, `lastsync` = ? WHERE `id` = ?',
                [
                    $feed,
                    $title,
                    $website,
                    $description,
                    $language,
                    $generator,
                    $copyright,
                    $totalSkip,
                    $totalCount,
                    $episodeCount,
                    $lastBuildDate->getTimestamp(),
                    $lastSyncDate->getTimestamp(),
                    $podcastId
                ]
            );

        $podcast->expects(static::once())
            ->method('getFeedUrl')
            ->willReturn($feed);
        $podcast->expects(static::once())
            ->method('getTitle')
            ->willReturn($title);
        $podcast->expects(static::once())
            ->method('getWebsite')
            ->willReturn($website);
        $podcast->expects(static::once())
            ->method('getDescription')
            ->willReturn($description);
        $podcast->expects(static::once())
            ->method('getLanguage')
            ->willReturn($language);
        $podcast->expects(static::once())
            ->method('getGenerator')
            ->willReturn($generator);
        $podcast->expects(static::once())
            ->method('getCopyright')
            ->willReturn($copyright);
        $podcast->expects(static::once())
            ->method('getTotalSkip')
            ->willReturn($totalSkip);
        $podcast->expects(static::once())
            ->method('getTotalCount')
            ->willReturn($totalCount);
        $podcast->expects(static::once())
            ->method('getEpisodeCount')
            ->willReturn($episodeCount);
        $podcast->expects(static::once())
            ->method('getLastBuildDate')
            ->willReturn($lastBuildDate);
        $podcast->expects(static::once())
            ->method('getLastSyncDate')
            ->willReturn($lastSyncDate);
        $podcast->expects(static::once())
            ->method('getId')
            ->willReturn($podcastId);

        static::assertNull(
            $this->subject->persist($podcast)
        );
    }

    public function testFindByIdReturnsNullIfNotFound(): void
    {
        $podcastId = 666;

        $podcast = $this->createMock(Podcast::class);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with($podcastId)
            ->willReturn($podcast);

        $podcast->expects(static::once())
            ->method('isNew')
            ->willReturn(true);

        static::assertNull(
            $this->subject->findById($podcastId)
        );
    }

    public function testFindByIdReturnsObject(): void
    {
        $podcastId = 666;

        $podcast = $this->createMock(Podcast::class);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with($podcastId)
            ->willReturn($podcast);

        $podcast->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        static::assertSame(
            $podcast,
            $this->subject->findById($podcastId)
        );
    }

    public function testPrototypeReturnsNewObject(): void
    {
        static::assertInstanceOf(
            Podcast::class,
            $this->subject->prototype()
        );
    }
}
