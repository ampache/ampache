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

namespace Ampache\Module\Art\Export;

use Ahc\Cli\IO\Interactor;
use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Art\Export\Exception\ArtExportException;
use Ampache\Module\Art\Export\Writer\MetadataWriter;
use Ampache\Module\Art\Export\Writer\MetadataWriterInterface;
use Ampache\Repository\ImageRepositoryInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ArtExporterTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private ImageRepositoryInterface&MockObject $imageRepository;
    private LoggerInterface&MockObject $logger;
    private ArtExporter $subject;

    public function testExportDoesNothingWhenNoImagesExist(): void
    {
        $interactor     = $this->createMock(Interactor::class);
        $metadataWriter = $this->createMock(MetadataWriterInterface::class);

        $this->imageRepository->expects(static::once())
            ->method('findAllImage')
            ->willReturnCallback(static function (): iterable {
                yield from [];
            });

        $this->imageRepository->expects(static::never())
            ->method('deleteImage');

        $this->subject->export($interactor, $metadataWriter, false);

        $this->addToAssertionCount(1);
    }

    public function testExportThrowsExceptionWhenLocalMetadataDirIsNotConfigured(): void
    {
        $interactor     = $this->createMock(Interactor::class);
        $metadataWriter = $this->createMock(MetadataWriterInterface::class);

        $this->imageRepository->expects(static::once())
            ->method('findAllImage')
            ->willReturnCallback(static function (): iterable {
                yield [
                    'id' => 1,
                    'object_id' => 21,
                    'object_type' => 'album',
                    'size' => 'original',
                    'mime' => 'image/png',
                ];
            });

        $this->expectException(ArtExportException::class);
        $this->expectExceptionMessage('local_metadata_dir setting is required to store art on disk');

        $this->subject->export($interactor, $metadataWriter, false);
    }

    public function testExportThrowsExceptionWhenMetadataWriterFailsToCreateTheFile(): void
    {
        $interactor     = $this->createMock(Interactor::class);
        $metadataWriter = $this->createMock(MetadataWriterInterface::class);
        $root           = vfsStream::setup();

        AmpConfig::set('local_metadata_dir', $root->url(), true);

        $this->imageRepository->method('findAllImage')
            ->willReturnCallback(static function (): iterable {
                yield [
                    'id' => 1,
                    'object_id' => 21,
                    'object_type' => 'album',
                    'size' => 'original',
                    'mime' => 'image/png',
                ];
            });

        $this->expectException(ArtExportException::class);
        $this->expectExceptionMessage(sprintf(
            'Unable to open `%s/album/21/default/art-original.png` for writing',
            $root->url()
        ));

        $this->subject->export($interactor, $metadataWriter, false);
    }

    public function testExportWritesRawArtToDiskViaMetadataWriterAndClearsDatabaseImage(): void
    {
        $interactor     = $this->createMock(Interactor::class);
        $metadataWriter = new MetadataWriter();
        $root           = vfsStream::setup();

        AmpConfig::set('local_metadata_dir', $root->url(), true);

        $this->configContainer->method('get')
            ->with('album_art_store_disk')
            ->willReturn(true);

        $this->imageRepository->expects(static::once())
            ->method('findAllImage')
            ->willReturnCallback(static function (): iterable {
                yield [
                    'id' => 1,
                    'object_id' => 21,
                    'object_type' => 'album',
                    'size' => 'original',
                    'mime' => 'image/png',
                ];
            });

        $this->imageRepository->expects(static::once())
            ->method('getRawImage')
            ->with(21, 'album', 'original', 'image/png')
            ->willReturn('some-raw-bytes');

        $this->imageRepository->expects(static::once())
            ->method('deleteImage')
            ->with(1);

        $this->subject->export($interactor, $metadataWriter, true);

        self::assertSame(
            'some-raw-bytes',
            file_get_contents($root->url() . '/album/21/default/art-original.png')
        );
    }

    protected function setUp(): void
    {
        $this->logger           = $this->createMock(LoggerInterface::class);
        $this->configContainer  = $this->createMock(ConfigContainerInterface::class);
        $this->imageRepository  = $this->createMock(ImageRepositoryInterface::class);

        $this->subject = new ArtExporter(
            $this->logger,
            $this->configContainer,
            $this->imageRepository,
        );

        AmpConfig::set('local_metadata_dir', '', true);
    }
}
