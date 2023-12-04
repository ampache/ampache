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
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PodcastRepositoryTest extends TestCase
{
    private ModelFactoryInterface&MockObject $modelFactory;

    private DatabaseConnectionInterface&MockObject $connection;

    private PodcastRepository $subject;

    protected function setUp(): void
    {
        $this->modelFactory = $this->createMock(ModelFactoryInterface::class);
        $this->connection   = $this->createMock(DatabaseConnectionInterface::class);

        $this->subject = new PodcastRepository(
            $this->modelFactory,
            $this->connection
        );
    }

    public function testFindByFeedUrlReturnsNullIfNothingWasFound(): void
    {
        $feedUrl = 'some-url';

        $this->connection->expects(static::once())
            ->method('fetchOne')
            ->with(
                'SELECT `id` FROM `podcast` WHERE `feed`= ?',
                [
                    $feedUrl
                ]
            )
            ->willReturn(false);

        static::assertNull(
            $this->subject->findByFeedUrl($feedUrl)
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
                'SELECT `id` FROM `podcast` WHERE `feed`= ?',
                [
                    $feedUrl
                ]
            )
            ->willReturn((string) $podcastId);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with($podcastId)
            ->willReturn($podcast);

        static::assertSame(
            $podcast,
            $this->subject->findByFeedUrl($feedUrl)
        );
    }

    public function testCreateReturnsPodcast(): void
    {
        $title         = 'some-title';
        $website       = 'some-website';
        $description   = 'some-description';
        $language      = 'some-language';
        $copyright     = 'some-copyright';
        $generator     = 'some-generator';
        $lastBuildDate = 666;
        $feedUrl       = 'some-feed-url';
        $catalogId     = 42;
        $podcastId     = 21;

        $catalog = $this->createMock(Catalog::class);
        $podcast = $this->createMock(Podcast::class);

        $this->connection->expects(static::once())
            ->method('query')
            ->with(
                <<<SQL
                INSERT INTO
                    `podcast`
                    (
                        `feed`,
                        `catalog`,
                        `title`,
                        `website`,
                        `description`,
                        `language`,
                        `copyright`,
                        `generator`,
                        `lastbuilddate`
                    )
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?)
                SQL,
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
            );
        $this->connection->expects(static::once())
            ->method('getLastInsertedId')
            ->willReturn($podcastId);

        $this->modelFactory->expects(static::once())
            ->method('createPodcast')
            ->with($podcastId)
            ->willReturn($podcast);

        $catalog->expects(static::once())
            ->method('getId')
            ->willReturn($catalogId);

        static::assertSame(
            $podcast,
            $this->subject->create(
                $catalog,
                $feedUrl,
                [
                    'title' => $title,
                    'website' => $website,
                    'description' => $description,
                    'language' => $language,
                    'copyright' => $copyright,
                    'generator' => $generator,
                    'lastBuildDate' => $lastBuildDate,
                ]
            )
        );
    }
}
