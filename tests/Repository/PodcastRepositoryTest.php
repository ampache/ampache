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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use DateTime;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\PhpUnit\Helper\ConsecutiveParams;

class PodcastRepositoryTest extends TestCase
{
    use ConsecutiveParams;

    private ModelFactoryInterface&MockObject $modelFactory;

    private DatabaseConnectionInterface&MockObject $connection;

    private PodcastRepository $subject;

    protected function setUp(): void
    {
        $this->modelFactory = $this->createMock(ModelFactoryInterface::class);
        $this->connection   = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new PodcastRepository(
            $this->modelFactory,
            $this->connection,
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
