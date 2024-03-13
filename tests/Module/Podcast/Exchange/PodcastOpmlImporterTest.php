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

namespace Ampache\Module\Podcast\Exchange;

use Ampache\Module\Podcast\Exception\FeedNotLoadableException;
use Ampache\Module\Podcast\Exception\InvalidFeedUrlException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Catalog;
use ArrayIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PodcastOpmlImporterTest extends TestCase
{
    private PodcastOpmlLoaderInterface&MockObject $podcastOpmlLoader;

    private PodcastCreatorInterface&MockObject $podcastCreator;

    private LoggerInterface&MockObject $logger;

    private PodcastOpmlImporter $subject;

    protected function setUp(): void
    {
        $this->podcastOpmlLoader = $this->createMock(PodcastOpmlLoaderInterface::class);
        $this->podcastCreator    = $this->createMock(PodcastCreatorInterface::class);
        $this->logger            = $this->createMock(LoggerInterface::class);

        $this->subject = new PodcastOpmlImporter(
            $this->podcastOpmlLoader,
            $this->podcastCreator,
            $this->logger
        );
    }

    public function testImportSkipsIfUrlIsInvalid(): void
    {
        $catalog = $this->createMock(Catalog::class);

        $xml     = 'some-xml';
        $feedUrl = 'some-url';

        $this->podcastOpmlLoader->expects(static::once())
            ->method('load')
            ->with($xml)
            ->willReturn(new ArrayIterator([$feedUrl]));

        $this->podcastCreator->expects(static::once())
            ->method('create')
            ->with($feedUrl, $catalog)
            ->willThrowException(new InvalidFeedUrlException());

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                sprintf('Importing feed: %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastOpmlImporter::class]
            );
        $this->logger->expects(static::once())
            ->method('warning')
            ->with(
                sprintf('Feed-url invalid: %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastOpmlImporter::class]
            );

        $this->subject->import($catalog, $xml);
    }

    public function testImportSkipsIfFeedUrlIsNotLoadable(): void
    {
        $catalog = $this->createMock(Catalog::class);

        $xml     = 'some-xml';
        $feedUrl = 'some-url';

        $this->podcastOpmlLoader->expects(static::once())
            ->method('load')
            ->with($xml)
            ->willReturn(new ArrayIterator([$feedUrl]));

        $this->podcastCreator->expects(static::once())
            ->method('create')
            ->with($feedUrl, $catalog)
            ->willThrowException(new FeedNotLoadableException());

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                sprintf('Importing feed: %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastOpmlImporter::class]
            );
        $this->logger->expects(static::once())
            ->method('warning')
            ->with(
                sprintf('Feed-url not readable: %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastOpmlImporter::class]
            );

        $this->subject->import($catalog, $xml);
    }

    public function testImportReturnsCountOfImportedFeeds(): void
    {
        $catalog = $this->createMock(Catalog::class);

        $xml     = 'some-xml';
        $feedUrl = 'some-url';

        $this->podcastOpmlLoader->expects(static::once())
            ->method('load')
            ->with($xml)
            ->willReturn(new ArrayIterator([$feedUrl]));

        $this->podcastCreator->expects(static::once())
            ->method('create')
            ->with($feedUrl, $catalog);

        $this->logger->expects(static::once())
            ->method('debug')
            ->with(
                sprintf('Importing feed: %s', $feedUrl),
                [LegacyLogger::CONTEXT_TYPE => PodcastOpmlImporter::class]
            );

        static::assertSame(
            1,
            $this->subject->import($catalog, $xml)
        );
    }
}
