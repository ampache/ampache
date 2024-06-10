<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\Catalog\CatalogLoaderInterface;
use Ampache\Module\Catalog\Exception\CatalogLoadingException;
use Ampache\Module\Podcast\Exception\PodcastFolderException;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Podcast;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PodcastFolderProviderTest extends TestCase
{
    private CatalogLoaderInterface&MockObject $catalogLoader;

    private PodcastFolderProvider $subject;

    private vfsStreamDirectory $rootFolder;

    protected function setUp(): void
    {
        $this->catalogLoader = $this->createMock(CatalogLoaderInterface::class);

        $this->subject = new PodcastFolderProvider(
            $this->catalogLoader,
        );

        $this->rootFolder = vfsStream::setup('/podcast');
    }

    public function testGetBaseFolderErrorsIfCatalogWasNotFound(): void
    {
        $catalogId = 666;

        $podcast = $this->createMock(Podcast::class);

        $podcast->expects(static::once())
            ->method('getCatalogId')
            ->willReturn($catalogId);

        static::expectException(PodcastFolderException::class);
        static::expectExceptionMessage(sprintf('Catalog not found: %d', $catalogId));

        $this->catalogLoader->expects(static::once())
            ->method('getById')
            ->with($catalogId)
            ->willThrowException(new CatalogLoadingException());

        $this->subject->getBaseFolder($podcast);
    }

    public function testGetBaseFolderErrorsOnInvalidCatalogType(): void
    {
        $catalogId = 666;

        $podcast = $this->createMock(Podcast::class);
        $catalog = $this->createMock(Catalog::class);

        static::expectException(PodcastFolderException::class);
        static::expectExceptionMessage('Bad catalog type: snafu');

        $podcast->expects(static::once())
            ->method('getCatalogId')
            ->willReturn($catalogId);

        $this->catalogLoader->expects(static::once())
            ->method('getById')
            ->with($catalogId)
            ->willReturn($catalog);

        $catalog->expects(static::once())
            ->method('get_type')
            ->willReturn('snafu');

        $this->subject->getBaseFolder($podcast);
    }

    public function testGetBaseFolderErrorsIfFolderCreationFails(): void
    {
        $catalogId    = 666;
        $catalogPath  = $this->rootFolder->url();
        $podcastTitle = '/some/path/and/some-title';

        $podcast = $this->createMock(Podcast::class);
        $catalog = $this->createMock(Catalog::class);

        static::expectException(PodcastFolderException::class);
        static::expectExceptionMessage(sprintf('Cannot create folder: %s/%s', $catalogPath, $podcastTitle));

        $this->catalogLoader->expects(static::once())
            ->method('getById')
            ->with($catalogId)
            ->willReturn($catalog);

        $catalog->expects(static::once())
            ->method('get_type')
            ->willReturn('local');
        $catalog->expects(static::once())
            ->method('get_path')
            ->willReturn($this->rootFolder->url());

        $podcast->expects(static::once())
            ->method('get_fullname')
            ->willReturn($podcastTitle);
        $podcast->expects(static::once())
            ->method('getCatalogId')
            ->willReturn($catalogId);

        $this->subject->getBaseFolder($podcast);
    }

    public function testGetBaseFolderReturnsCreatedFolder(): void
    {
        $catalogId    = 666;
        $podcastTitle = 'some-title';

        $podcast = $this->createMock(Podcast::class);
        $catalog = $this->createMock(Catalog::class);

        $this->catalogLoader->expects(static::once())
            ->method('getById')
            ->with($catalogId)
            ->willReturn($catalog);

        $catalog->expects(static::once())
            ->method('get_type')
            ->willReturn('local');
        $catalog->expects(static::once())
            ->method('get_path')
            ->willReturn($this->rootFolder->url());

        $podcast->expects(static::once())
            ->method('get_fullname')
            ->willReturn($podcastTitle);
        $podcast->expects(static::once())
            ->method('getCatalogId')
            ->willReturn($catalogId);

        static::assertSame(
            sprintf('%s/%s', $this->rootFolder->url(), $podcastTitle),
            $this->subject->getBaseFolder($podcast)
        );
    }

    public function testGetBaseFolderReturnsExistingFolder(): void
    {
        $catalogId    = 666;
        $podcastTitle = 'some-title';

        $this->rootFolder->addChild(new vfsStreamDirectory($podcastTitle));

        $podcast = $this->createMock(Podcast::class);
        $catalog = $this->createMock(Catalog::class);

        $this->catalogLoader->expects(static::once())
            ->method('getById')
            ->with($catalogId)
            ->willReturn($catalog);

        $catalog->expects(static::once())
            ->method('get_type')
            ->willReturn('local');
        $catalog->expects(static::once())
            ->method('get_path')
            ->willReturn($this->rootFolder->url());

        $podcast->expects(static::once())
            ->method('get_fullname')
            ->willReturn($podcastTitle);
        $podcast->expects(static::once())
            ->method('getCatalogId')
            ->willReturn($catalogId);

        static::assertSame(
            sprintf('%s/%s', $this->rootFolder->url(), $podcastTitle),
            $this->subject->getBaseFolder($podcast)
        );
    }
}
