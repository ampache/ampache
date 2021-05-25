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
 *
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\MockeryTestCase;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\PodcastInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Mockery\MockInterface;

class PodcastRepositoryTest extends MockeryTestCase
{
    /** @var MockInterface|Connection */
    private MockInterface $connection;

    /** @var MockInterface|ModelFactoryInterface */
    private MockInterface $modelFactory;

    private PodcastRepository $subject;

    public function setUp(): void
    {
        $this->connection   = $this->mock(Connection::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new PodcastRepository(
            $this->connection,
            $this->modelFactory
        );
    }

    public function testGetPodcastIdsReturnsList(): void
    {
        $catalogId = 666;
        $podcastId = 42;

        $result = $this->mock(Result::class);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `podcast` WHERE `catalog` = ?',
                [$catalogId]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->twice()
            ->andReturn((string) $podcastId, false);

        $this->assertSame(
            [$podcastId],
            $this->subject->getPodcastIds($catalogId)
        );
    }

    public function testRemoveRemoves(): void
    {
        $podcast = $this->mock(PodcastInterface::class);
        $result  = $this->mock(Result::class);

        $podcastId = 666;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'DELETE FROM `podcast` WHERE `id` = ?',
                [$podcastId]
            )
            ->once()
            ->andReturn($result);

        $podcast->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastId);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(1);

        $this->assertTrue(
            $this->subject->remove($podcast)
        );
    }

    public function testUpdateLastsyncUpdates(): void
    {
        $podcast = $this->mock(PodcastInterface::class);

        $podcastId = 666;
        $time      = 42;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE `podcast` SET `lastsync` = ? WHERE `id` = ?',
                [$time, $podcastId]
            )
            ->once();

        $podcast->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($podcastId);

        $this->subject->updateLastsync($podcast, $time);
    }

    public function testUpdateWritesData(): void
    {
        $podcastId   = 666;
        $feed        = 'some-feed';
        $title       = 'some-title';
        $website     = 'some-website';
        $description = 'some-description';
        $generator   = 'some-generator';
        $copyright   = 'some-copyright';

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `generator` = ?, `copyright` = ? WHERE `id` = ?',
                [$feed, $title, $website, $description, $generator, $copyright, $podcastId]
            )
            ->once();

        $this->subject->update(
            $podcastId,
            $feed,
            $title,
            $website,
            $description,
            $generator,
            $copyright
        );
    }

    public function testInsertWritesDataAndReturnsNullOnFailure(): void
    {
        $catalogId     = 666;
        $lastBuildDate = 42;
        $feedUrl       = 'some-feed-url';
        $title         = 'some-title';
        $website       = 'some-website';
        $description   = 'some-description';
        $language      = 'some-language';
        $generator     = 'some-generator';
        $copyright     = 'some-copyright';

        $result = $this->mock(Result::class);

        $sql = <<<SQL
        INSERT INTO
            `podcast`
            (`feed`, `catalog`, `title`, `website`, `description`, `language`, `copyright`, `generator`, `lastbuilddate`)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                [
                    $feedUrl,
                    $catalogId,
                    $title,
                    $website,
                    $description,
                    $language,
                    $copyright,
                    $generator,
                    $lastBuildDate
                ]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(0);

        $this->assertNull(
            $this->subject->insert(
                $feedUrl,
                $catalogId,
                $title,
                $website,
                $description,
                $language,
                $copyright,
                $generator,
                $lastBuildDate
            )
        );
    }

    public function testInsertWritesDataAndReturnsInsertedIdOnSuccess(): void
    {
        $catalogId     = 666;
        $lastBuildDate = 42;
        $feedUrl       = 'some-feed-url';
        $title         = 'some-title';
        $website       = 'some-website';
        $description   = 'some-description';
        $language      = 'some-language';
        $generator     = 'some-generator';
        $copyright     = 'some-copyright';
        $podcastId     = 33;

        $result = $this->mock(Result::class);

        $sql = <<<SQL
        INSERT INTO
            `podcast`
            (`feed`, `catalog`, `title`, `website`, `description`, `language`, `copyright`, `generator`, `lastbuilddate`)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;

        $this->connection->shouldReceive('executeQuery')
            ->with(
                $sql,
                [
                    $feedUrl,
                    $catalogId,
                    $title,
                    $website,
                    $description,
                    $language,
                    $copyright,
                    $generator,
                    $lastBuildDate
                ]
            )
            ->once()
            ->andReturn($result);
        $this->connection->shouldReceive('lastInsertId')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $podcastId);

        $result->shouldReceive('rowCount')
            ->withNoArgs()
            ->once()
            ->andReturn(1);

        $this->assertSame(
            $podcastId,
            $this->subject->insert(
                $feedUrl,
                $catalogId,
                $title,
                $website,
                $description,
                $language,
                $copyright,
                $generator,
                $lastBuildDate
            )
        );
    }

    public function testFindByFeedUrlReturnsNullIfNothingWasFound(): void
    {
        $feedUrl = 'some-url';

        $result = $this->mock(Result::class);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `podcast` WHERE `feed`= ?',
                [$feedUrl]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->assertNull(
            $this->subject->findByFeedUrl($feedUrl)
        );
    }

    public function testFindByFeedUrlReturnsFoundPodcastId(): void
    {
        $feedUrl   = 'some-url';
        $podcastId = 666;

        $result = $this->mock(Result::class);

        $this->connection->shouldReceive('executeQuery')
            ->with(
                'SELECT `id` FROM `podcast` WHERE `feed`= ?',
                [$feedUrl]
            )
            ->once()
            ->andReturn($result);

        $result->shouldReceive('fetchOne')
            ->withNoArgs()
            ->once()
            ->andReturn((string) $podcastId);

        $this->assertSame(
            $podcastId,
            $this->subject->findByFeedUrl($feedUrl)
        );
    }

    public function testFindByIdReturnsNullIfNew(): void
    {
        $id = 666;

        $podcast = $this->mock(PodcastInterface::class);

        $this->modelFactory->shouldReceive('createPodcast')
            ->with($id)
            ->once()
            ->andReturn($podcast);

        $podcast->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->assertNull(
            $this->subject->findById($id)
        );
    }

    public function testFindByIdReturnsObject(): void
    {
        $id = 666;

        $podcast = $this->mock(PodcastInterface::class);

        $this->modelFactory->shouldReceive('createPodcast')
            ->with($id)
            ->once()
            ->andReturn($podcast);

        $podcast->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            $podcast,
            $this->subject->findById($id)
        );
    }

    public function testGetDataByIdReturnsEmptyArrayIfNothingWasFound(): void
    {
        $podcastId = 666;

        $this->connection->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `podcast` WHERE `id`= ?',
                [$podcastId]
            )
            ->once()
            ->andReturnFalse();

        $this->assertSame(
            [],
            $this->subject->getDataById($podcastId)
        );
    }

    public function testGetDataByIdReturnsData(): void
    {
        $podcastId = 666;
        $result    = ['some' => 'data'];

        $this->connection->shouldReceive('fetchAssociative')
            ->with(
                'SELECT * FROM `podcast` WHERE `id`= ?',
                [$podcastId]
            )
            ->once()
            ->andReturn($result);

        $this->assertSame(
            $result,
            $this->subject->getDataById($podcastId)
        );
    }
}
