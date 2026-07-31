<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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
 */

namespace Ampache\Module\Catalog\GarbageCollector;

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Module\Label\LabelGarbageCollectorInterface;
use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\SearchRepositoryInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\TagRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Ampache\Repository\WantedRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CatalogGarbageCollectorTest extends TestCase
{
    private AlbumRepositoryInterface&MockObject $albumRepository;
    private ArtCleanupInterface&MockObject $artCleanup;
    private ArtistRepositoryInterface&MockObject $artistRepository;
    private BookmarkRepositoryInterface&MockObject $bookmarkRepository;
    private ContainerInterface&MockObject $dic;
    private FolderRepositoryInterface&MockObject $folderRepository;
    private LabelGarbageCollectorInterface&MockObject $labelGarbageCollector;
    private LabelRepositoryInterface&MockObject $labelRepository;
    private MetadataManagerInterface&MockObject $metadataManager;
    private PlaylistRepositoryInterface&MockObject $playlistRepository;
    private PodcastEpisodeRepositoryInterface&MockObject $podcastEpisodeRepository;
    private SearchRepositoryInterface&MockObject $searchRepository;
    private ShoutRepositoryInterface&MockObject $shoutRepository;
    private CatalogGarbageCollector $subject;
    private TagRepositoryInterface&MockObject $tagRepository;
    private UserActivityRepositoryInterface&MockObject $userActivityRepository;
    private UserRepositoryInterface&MockObject $userRepository;
    private VideoRepositoryInterface&MockObject $videoRepository;
    private WantedRepositoryInterface&MockObject $wantedRepository;

    public function testCollectRunsGarbageCollectionOnEveryInjectedRepository(): void
    {
        $this->albumRepository->expects(static::once())->method('collectGarbage');
        $this->bookmarkRepository->expects(static::once())->method('collectGarbage');
        $this->shoutRepository->expects(static::once())->method('collectGarbage');
        $this->userActivityRepository->expects(static::once())->method('collectGarbage');
        $this->userRepository->expects(static::once())->method('collectGarbage');
        $this->metadataManager->expects(static::once())->method('collectGarbage');
        $this->podcastEpisodeRepository->expects(static::once())->method('collectGarbage');
        $this->wantedRepository->expects(static::once())->method('collectGarbage');
        $this->labelRepository->expects(static::once())->method('collectGarbage');
        $this->artCleanup->expects(static::once())->method('collectGarbage');
        $this->artistRepository->expects(static::once())->method('collectGarbage');
        $this->folderRepository->expects(static::once())->method('collectGarbage');
        $this->videoRepository->expects(static::once())->method('collectGarbage');
        $this->playlistRepository->expects(static::once())->method('collectGarbage');
        $this->searchRepository->expects(static::once())->method('collectGarbage');
        $this->labelGarbageCollector->expects(static::once())->method('collect');

        $this->subject->collect();
    }

    protected function setUp(): void
    {
        $this->albumRepository          = $this->createMock(AlbumRepositoryInterface::class);
        $this->bookmarkRepository       = $this->createMock(BookmarkRepositoryInterface::class);
        $this->shoutRepository          = $this->createMock(ShoutRepositoryInterface::class);
        $this->userActivityRepository   = $this->createMock(UserActivityRepositoryInterface::class);
        $this->userRepository           = $this->createMock(UserRepositoryInterface::class);
        $this->metadataManager          = $this->createMock(MetadataManagerInterface::class);
        $this->podcastEpisodeRepository = $this->createMock(PodcastEpisodeRepositoryInterface::class);
        $this->wantedRepository         = $this->createMock(WantedRepositoryInterface::class);
        $this->labelRepository          = $this->createMock(LabelRepositoryInterface::class);
        $this->artCleanup               = $this->createMock(ArtCleanupInterface::class);
        $this->artistRepository         = $this->createMock(ArtistRepositoryInterface::class);
        $this->folderRepository         = $this->createMock(FolderRepositoryInterface::class);
        $this->videoRepository          = $this->createMock(VideoRepositoryInterface::class);
        $this->playlistRepository       = $this->createMock(PlaylistRepositoryInterface::class);
        $this->searchRepository         = $this->createMock(SearchRepositoryInterface::class);
        $this->labelGarbageCollector    = $this->createMock(LabelGarbageCollectorInterface::class);
        $this->tagRepository            = $this->createMock(TagRepositoryInterface::class);
        $this->dic                      = $this->createMock(ContainerInterface::class);

        // debug_event() pulls the logger off the same container, so this cannot be a single-service stub
        $this->dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            TagRepositoryInterface::class => $this->tagRepository,
            UserRepositoryInterface::class => $this->userRepository,
            default => $this->createMock(LoggerInterface::class),
        });

        // `Tag` reaches its repository through the `global $dic` bridge; phpunit.xml sets backupGlobals
        // so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;

        $this->subject = new CatalogGarbageCollector(
            $this->albumRepository,
            $this->bookmarkRepository,
            $this->shoutRepository,
            $this->userActivityRepository,
            $this->userRepository,
            $this->metadataManager,
            $this->podcastEpisodeRepository,
            $this->wantedRepository,
            $this->labelRepository,
            $this->artCleanup,
            $this->artistRepository,
            $this->folderRepository,
            $this->videoRepository,
            $this->playlistRepository,
            $this->searchRepository,
            $this->labelGarbageCollector,
        );
    }
}
