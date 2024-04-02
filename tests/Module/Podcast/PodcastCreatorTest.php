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
 *
 */

namespace Ampache\Module\Podcast;

use Ampache\Module\Podcast\Exception\InvalidCatalogException;
use Ampache\Module\Podcast\Exception\InvalidFeedUrlException;
use Ampache\Module\Podcast\Feed\FeedLoaderInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PodcastCreatorTest extends TestCase
{
    private FeedLoaderInterface&MockObject $feedLoader;

    private PodcastRepositoryInterface&MockObject $podcastRepository;

    private PodcastSyncerInterface&MockObject $podcastSyncer;

    private PodcastFolderProviderInterface&MockObject $podcastFolderProvider;

    private LoggerInterface&MockObject $logger;

    private PodcastCreator $subject;

    private Catalog&MockObject $catalog;

    protected function setUp(): void
    {
        $this->feedLoader            = $this->createMock(FeedLoaderInterface::class);
        $this->podcastRepository     = $this->createMock(PodcastRepositoryInterface::class);
        $this->podcastSyncer         = $this->createMock(PodcastSyncerInterface::class);
        $this->podcastFolderProvider = $this->createMock(PodcastFolderProviderInterface::class);
        $this->logger                = $this->createMock(LoggerInterface::class);

        $this->subject = new PodcastCreator(
            $this->feedLoader,
            $this->podcastRepository,
            $this->podcastSyncer,
            $this->podcastFolderProvider,
            $this->logger,
        );

        $this->catalog = $this->createMock(Catalog::class);
    }

    public function testCreateFailsIfFeedUrlIsMalformed(): void
    {
        static::expectException(InvalidFeedUrlException::class);

        $this->subject->create(
            'ftp://not-available',
            $this->catalog
        );
    }

    public function testCreateFailsIfCatalogDoesNotSupportPodcasts(): void
    {
        static::expectException(InvalidCatalogException::class);

        $this->catalog->expects(static::once())
            ->method('supportsType')
            ->with('podcast')
            ->willReturn(false);

        $this->subject->create(
            'https://zomglol',
            $this->catalog
        );
    }
}
