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
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\Artist\Tag;

use Ampache\Module\Album\Tag\AlbumTagUpdaterInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\TagRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ArtistTagUpdaterTest extends TestCase
{
    private AlbumRepositoryInterface&MockObject $albumRepository;
    private AlbumTagUpdaterInterface&MockObject $albumTagUpdater;
    private ContainerInterface&MockObject $dic;
    private ModelFactoryInterface&MockObject $modelFactory;
    private ArtistTagUpdater $subject;
    private TagRepositoryInterface&MockObject $tagRepository;

    public function testUpdateTagsDoesNotTouchAlbumsWhenNoChildPropagationRequested(): void
    {
        $artist     = $this->createMock(Artist::class);
        $artist->id = 21;

        $this->albumRepository->expects(static::never())
            ->method('getAlbumByArtist');

        $this->subject->updateTags($artist, 'rock,pop', false, false);
    }

    public function testUpdateTagsPropagatesToAlbumsWhenAddingToChilds(): void
    {
        $artist     = $this->createMock(Artist::class);
        $artist->id = 21;
        $album      = $this->createMock(Album::class);
        $albumId    = 42;

        $this->albumRepository->expects(static::once())
            ->method('getAlbumByArtist')
            ->with(21)
            ->willReturn([$albumId]);

        $this->modelFactory->expects(static::once())
            ->method('createAlbum')
            ->with($albumId)
            ->willReturn($album);

        $this->albumTagUpdater->expects(static::once())
            ->method('updateTags')
            ->with($album, 'rock,pop', false, true);

        $this->subject->updateTags($artist, 'rock,pop', false, true);
    }

    public function testUpdateTagsPropagatesToAlbumsWhenOverridingChilds(): void
    {
        $artist     = $this->createMock(Artist::class);
        $artist->id = 21;
        $album      = $this->createMock(Album::class);
        $albumId    = 42;

        $this->albumRepository->expects(static::once())
            ->method('getAlbumByArtist')
            ->with(21)
            ->willReturn([$albumId]);

        $this->modelFactory->expects(static::once())
            ->method('createAlbum')
            ->with($albumId)
            ->willReturn($album);

        $this->albumTagUpdater->expects(static::once())
            ->method('updateTags')
            ->with($album, 'rock,pop', true, false);

        $this->subject->updateTags($artist, 'rock,pop', true, false);
    }

    protected function setUp(): void
    {
        $this->albumRepository = $this->createMock(AlbumRepositoryInterface::class);
        $this->albumTagUpdater = $this->createMock(AlbumTagUpdaterInterface::class);
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);
        $this->tagRepository   = $this->createMock(TagRepositoryInterface::class);
        $this->dic             = $this->createMock(ContainerInterface::class);

        // debug_event() pulls the logger off the same container, so this cannot be a single-service stub
        $this->dic->method('get')->willReturnCallback(fn(string $id): object => match ($id) {
            TagRepositoryInterface::class => $this->tagRepository,
            default => $this->createMock(LoggerInterface::class),
        });

        // `Tag` reaches its repository through the `global $dic` bridge; phpunit.xml sets backupGlobals
        // so the real container is restored after every test
        $GLOBALS['dic'] = $this->dic;

        $this->subject = new ArtistTagUpdater(
            $this->albumRepository,
            $this->albumTagUpdater,
            $this->modelFactory,
        );
    }
}
