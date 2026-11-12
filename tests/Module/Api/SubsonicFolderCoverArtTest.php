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

namespace Ampache\Module\Api;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\OpenSubsonic\Handler\StreamingHandler as OpenSubsonicStreamingHandler;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\Subsonic\Handler\StreamingHandler;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Database\database_object;
use Ampache\Module\System\Dba;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\ImageRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use DI\Container;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

/** Album folders came up without cover art in a Subsonic folder browse (#4556) */
class SubsonicFolderCoverArtTest extends MockeryTestCase
{
    private const int ALBUM         = 55955;

    private const string ALBUM_ART  = 'album-png-bytes';
    private const int ALBUM_FOLDER  = 55952;
    private const int ART_FOLDER    = 55953;
    private const int ARTIST_FOLDER = 55951;
    private const string FOLDER_ART = 'folder-png-bytes';
    private const int SONG          = 55954;

    public function testAFolderHoldingTracksIsListedWithCoverArt(): void
    {
        $subject = new Subsonic_Json_Data($this->mock(AlbumRepositoryInterface::class), $this->folderRepository(), $this->mock(SongRepositoryInterface::class), $this->fields());

        $child = $subject->addDirectory(['subsonic-response' => []], new Folder(self::ARTIST_FOLDER))['subsonic-response']['directory']['child'][0];

        self::assertSame('fo-' . self::ALBUM_FOLDER, $child['coverArt'] ?? null, 'the client only asks for art it was told about');
    }

    public function testGetCoverArtPrefersTheFoldersOwnArt(): void
    {
        $this->stubContainer();

        $responseHandler = $this->mock(SubsonicResponseHandlerInterface::class);
        $responseHandler->shouldReceive('checkParameter')->andReturn('fo-' . self::ART_FOLDER);
        $responseHandler->shouldNotReceive('errorOutput');

        $subject = new StreamingHandler($responseHandler, $this->mock(Subsonic_Json_Data::class), $this->mock(Subsonic_Xml_Data::class));

        $this->expectOutputString(self::FOLDER_ART);
        $subject->getcoverart(['id' => 'fo-' . self::ART_FOLDER], $this->mock(User::class));
    }

    public function testGetCoverArtServesTheFirstTracksAlbumForAFolderWithoutArtOfItsOwn(): void
    {
        $this->stubContainer();

        $responseHandler = $this->mock(SubsonicResponseHandlerInterface::class);
        $responseHandler->shouldReceive('checkParameter')->andReturn('fo-' . self::ALBUM_FOLDER);
        $responseHandler->shouldNotReceive('errorOutput');

        $subject = new StreamingHandler($responseHandler, $this->mock(Subsonic_Json_Data::class), $this->mock(Subsonic_Xml_Data::class));

        $this->expectOutputString(self::ALBUM_ART);
        $subject->getcoverart(['id' => 'fo-' . self::ALBUM_FOLDER], $this->mock(User::class));
    }

    public function testOpenSubsonicGetCoverArtServesTheFirstTracksAlbumToo(): void
    {
        $this->stubContainer();

        $responseHandler = $this->mock(OpenSubsonicResponseHandlerInterface::class);
        $responseHandler->shouldReceive('checkParameter')->andReturn('fo-' . self::ALBUM_FOLDER);
        $responseHandler->shouldNotReceive('errorOutput');

        $subject = new OpenSubsonicStreamingHandler($responseHandler, $this->mock(OpenSubsonic_Json_Data::class), $this->mock(OpenSubsonic_Xml_Data::class));

        $this->expectOutputString(self::ALBUM_ART);
        $subject->getcoverart(['id' => 'fo-' . self::ALBUM_FOLDER], $this->mock(User::class));
    }

    public function testOpenSubsonicListsAFolderHoldingTracksWithCoverArt(): void
    {
        $subject = new OpenSubsonic_Json_Data($this->mock(AlbumRepositoryInterface::class), $this->folderRepository(), $this->fields(), $this->mock(SongRepositoryInterface::class));

        $child = $subject->addDirectory(['subsonic-response' => []], new Folder(self::ARTIST_FOLDER))['subsonic-response']['directory']['child'][0];

        self::assertSame('fo-' . self::ALBUM_FOLDER, $child['coverArt'] ?? null);
    }

    public function testTheXmlListingAdvertisesTheSameCoverArt(): void
    {
        $subject = new Subsonic_Xml_Data($this->mock(AlbumRepositoryInterface::class), $this->folderRepository(), $this->mock(SongRepositoryInterface::class), $this->fields());

        $xml = $subject->addDirectory(new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><subsonic-response/>'), new Folder(self::ARTIST_FOLDER));

        self::assertSame('fo-' . self::ALBUM_FOLDER, (string) $xml->directory->child[0]['coverArt']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // priming the cache keeps `new Folder()` and `new Song()` off the database
        $folderTable = (string) Dba::escape('folder');
        database_object::add_to_cache($folderTable, self::ARTIST_FOLDER, [
            'id' => self::ARTIST_FOLDER,
            'name' => 'Painter',
            'catalog' => 1,
            'parent' => null,
            'path_name' => '/music/painter',
            'playable' => 0,
        ]);
        database_object::add_to_cache($folderTable, self::ALBUM_FOLDER, [
            'id' => self::ALBUM_FOLDER,
            'name' => 'Canvas',
            'catalog' => 1,
            'parent' => self::ARTIST_FOLDER,
            'path_name' => '/music/painter/canvas',
            'playable' => 1,
        ]);
        database_object::add_to_cache($folderTable, self::ART_FOLDER, [
            'id' => self::ART_FOLDER,
            'name' => 'Sketch',
            'catalog' => 1,
            'parent' => self::ARTIST_FOLDER,
            'path_name' => '/music/painter/sketch',
            'playable' => 1,
        ]);
        database_object::add_to_cache('song', self::SONG, [
            'id' => self::SONG,
            'album' => self::ALBUM,
            'file' => '/music/painter/canvas/01.mp3',
        ]);

        // only the second album folder has art of its own
        database_object::add_to_cache('art_has_db_folder', self::ALBUM_FOLDER, [0]);
        database_object::add_to_cache('art_has_db_folder', self::ART_FOLDER, [1]);
    }

    private function fields(): OpenSubsonic_Fields
    {
        return new OpenSubsonic_Fields(
            $this->mock(BookmarkRepositoryInterface::class),
            $this->mock(LabelRepositoryInterface::class)
        );
    }

    /**
     * The artist folder holds one album folder
     */
    private function folderRepository(): FolderRepositoryInterface
    {
        $folderRepository = $this->mock(FolderRepositoryInterface::class);
        $folderRepository->shouldReceive('getObjects')
            ->with(self::ARTIST_FOLDER, -1)
            ->andReturn([['object_type' => LibraryItemEnum::FOLDER, 'object_id' => self::ALBUM_FOLDER]]);
        $folderRepository->shouldReceive('getMedias')
            ->withArgs(fn(Folder $folder, ?string $type): bool => $folder->getId() === self::ALBUM_FOLDER && $type === 'song')
            ->andReturn([['object_type' => LibraryItemEnum::SONG, 'object_id' => self::SONG]]);

        return $folderRepository;
    }

    /**
     * `Folder` and `Art` reach their repositories through the global container
     */
    private function stubContainer(): void
    {
        $imageRepository = $this->mock(ImageRepositoryInterface::class);
        $imageRepository->shouldReceive('getOriginalRow')
            ->andReturnUsing(fn(string $type, int $id): array => match ([$type, $id]) {
                ['album', self::ALBUM] => ['id' => 1, 'image' => self::ALBUM_ART, 'mime' => 'image/png', 'width' => 1, 'height' => 1, 'size' => 'original'],
                ['folder', self::ART_FOLDER] => ['id' => 2, 'image' => self::FOLDER_ART, 'mime' => 'image/png', 'width' => 1, 'height' => 1, 'size' => 'original'],
                default => [],
            });

        $folderRepository = $this->folderRepository();

        // escaping the folder table name without a connection is logged on the way to the cache
        $logger = $this->mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        $dic = $this->mock(Container::class);
        $dic->shouldReceive('get')->andReturnUsing(fn(string $id): object => match ($id) {
            FolderRepositoryInterface::class => $folderRepository,
            ImageRepositoryInterface::class => $imageRepository,
            default => $logger,
        });
        $GLOBALS['dic'] = $dic;
    }
}
