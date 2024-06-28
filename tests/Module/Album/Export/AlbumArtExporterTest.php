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

namespace Ampache\Module\Album\Export;

use Ampache\Module\Album\Export\Exception\AlbumArtExportException;
use Ahc\Cli\IO\Interactor;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Module\Album\Export\Writer\MetadataWriterInterface;
use Ampache\Repository\SongRepositoryInterface;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;

class AlbumArtExporterTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var MockInterface|SongRepositoryInterface|null */
    private MockInterface $songRepository;

    private ?AlbumArtExporter $subject;

    protected function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->songRepository  = $this->mock(SongRepositoryInterface::class);

        $this->subject = new AlbumArtExporter(
            $this->configContainer,
            $this->modelFactory,
            $this->songRepository
        );
    }

    public function testExportDoesNothingIfNoInfoExists(): void
    {
        $interacor      = $this->mock(Interactor::class);
        $catalog        = $this->mock(Catalog::class);
        $metadataWriter = $this->mock(MetadataWriterInterface::class);
        $art            = $this->mock(Art::class);

        $albumId = 666;

        $catalog->shouldReceive('get_album_ids')
            ->withNoArgs()
            ->once()
            ->andReturn([(string) $albumId]);

        $this->modelFactory->shouldReceive('createArt')
            ->with($albumId)
            ->once()
            ->andReturn($art);

        $art->shouldReceive('has_db_info')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->subject->export(
            $interacor,
            $catalog,
            $metadataWriter
        );
    }

    public function testExportThrowsExceptionIfFileCouldNotBeOpened(): void
    {
        $interactor     = $this->mock(Interactor::class);
        $catalog        = $this->mock(Catalog::class);
        $metadataWriter = $this->mock(MetadataWriterInterface::class);
        $art            = $this->mock(Art::class);
        $album          = $this->mock(Album::class);
        $song           = $this->mock(Song::class);
        $fs_root        = vfsStream::setup('', 0000);

        $albumId  = 666;
        $songId   = 42;
        $file     = $fs_root->url() . '/some-file';
        $raw_mime = '/some-raw-mime.png';

        $album->id = $albumId;

        $this->expectException(AlbumArtExportException::class);
        $this->expectExceptionMessage(
            'Unable to open `vfs:/folder.some-raw-mime.png` for writing',
        );

        $catalog->shouldReceive('get_album_ids')
            ->withNoArgs()
            ->once()
            ->andReturn([(string) $albumId]);

        $this->modelFactory->shouldReceive('createArt')
            ->with($albumId)
            ->once()
            ->andReturn($art);
        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);
        $this->modelFactory->shouldReceive('createSong')
            ->with($songId)
            ->once()
            ->andReturn($song);

        $this->songRepository->shouldReceive('getByAlbum')
            ->with($albumId, 1)
            ->once()
            ->andReturn([$songId]);

        $art->shouldReceive('has_db_info')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME)
            ->once()
            ->andReturn('');

        $art->raw_mime = $raw_mime;
        $song->file    = $file;

        $this->subject->export(
            $interactor,
            $catalog,
            $metadataWriter
        );
    }

    public function testExportThrowsExceptionIfFileCouldNotBeWritten(): void
    {
        $interactor     = $this->mock(Interactor::class);
        $catalog        = $this->mock(Catalog::class);
        $metadataWriter = $this->mock(MetadataWriterInterface::class);
        $art            = $this->mock(Art::class);
        $album          = $this->mock(Album::class);
        $song           = $this->mock(Song::class);
        $fs_root        = vfsStream::setup('');
        $file           = vfsStream::newFile('folder.png');

        $fs_root->addChild($file);

        $albumId   = 666;
        $songId    = 42;
        $file_name = $fs_root->url() . '/some-file';
        $raw_mime  = 'image/png';
        $raw_art   = 'some-raw-bytes';
        $fileName  = 'some-full-name';

        $album->id = $albumId;

        $catalog->shouldReceive('get_album_ids')
            ->withNoArgs()
            ->once()
            ->andReturn([(string) $albumId]);

        $this->modelFactory->shouldReceive('createArt')
            ->with($albumId)
            ->once()
            ->andReturn($art);
        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);
        $this->modelFactory->shouldReceive('createSong')
            ->with($songId)
            ->once()
            ->andReturn($song);

        $this->songRepository->shouldReceive('getByAlbum')
            ->with($albumId, 1)
            ->once()
            ->andReturn([$songId]);

        $art->shouldReceive('has_db_info')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::ALBUM_ART_PREFERRED_FILENAME)
            ->once()
            ->andReturn('//folder.png');

        $art->raw_mime = $raw_mime;
        $art->raw      = $raw_art;
        $song->file    = $file_name;

        $metadataWriter->shouldReceive('write')
            ->with(
                $fileName,
                'vfs:',
                'vfs:///folder.png'
            )
            ->once();

        $album->shouldReceive('get_fullname')
            ->with(true)
            ->once()
            ->andReturn($fileName);

        $this->subject->export(
            $interactor,
            $catalog,
            $metadataWriter
        );

        $this->assertSame(
            $raw_art,
            $file->getContent()
        );
    }
}
