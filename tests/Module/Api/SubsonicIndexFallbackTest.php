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
use Ampache\Module\Api\OpenSubsonic\Handler\BrowsingHandler as OpenSubsonicBrowsingHandler;
use Ampache\Module\Api\OpenSubsonic\MusicFolderResolverInterface as OpenSubsonicMusicFolderResolverInterface;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\SonicAnalysisPluginResolverInterface;
use Ampache\Module\Api\Subsonic\Handler\BrowsingHandler;
use Ampache\Module\Api\Subsonic\MusicFolderResolverInterface;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Database\Query\Random;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use DI\Container;
use Psr\Log\LoggerInterface;

/**
 * `getIndexes` answers with the folder tree, which only exists once a catalog scan has filled `folder_map`.
 * A library that has not been rescanned answered an empty index, and a client showed an empty library.
 */
class SubsonicIndexFallbackTest extends MockeryTestCase
{
    /** @var list<array{id: int, name: string, f_name: string}> */
    private const array ARTISTS = [
        ['id' => 7, 'name' => 'Alpha', 'f_name' => 'Alpha'],
    ];

    public function testAFolderTreeIsStillServedAsFolders(): void
    {
        $called = $this->serialiserFor(children: [['object_id' => 1, 'object_type' => 'folder']]);

        $this->assertSame('addFolderIndexes', $called);
    }

    public function testAnEmptyFolderTreeFallsBackToTheArtists(): void
    {
        $called = $this->serialiserFor(children: []);

        $this->assertSame('addIndexes', $called, 'without this the client is handed an empty library');
    }

    public function testOpenSubsonicFallsBackTheSameWay(): void
    {
        $this->assertSame('addIndexes', $this->openSubsonicSerialiserFor(children: []));
    }

    public function testOpenSubsonicStillServesAFolderTree(): void
    {
        $this->assertSame('addFolderIndexes', $this->openSubsonicSerialiserFor(children: [['object_id' => 1, 'object_type' => 'folder']]));
    }

    private function fields(): OpenSubsonic_Fields
    {
        return new OpenSubsonic_Fields(
            $this->mock(BookmarkRepositoryInterface::class),
            $this->mock(LabelRepositoryInterface::class),
            $this->mock(SongRepositoryInterface::class),
        );
    }

    /**
     * The same question asked of the OpenSubsonic handler, which carries the same code
     *
     * @param array<int, array<string, mixed>> $children
     */
    private function openSubsonicSerialiserFor(array $children): string
    {
        $this->stubContainer();

        $folderRepository = $this->mock(FolderRepositoryInterface::class);
        $folderRepository->shouldReceive('getCatalogRootChildren')->andReturn($children);

        $resolver = $this->mock(OpenSubsonicMusicFolderResolverInterface::class);
        $resolver->shouldReceive('musicFolders')->andReturn([3, 0]);

        $responseHandler = $this->mock(OpenSubsonicResponseHandlerInterface::class);
        $responseHandler->shouldReceive('addJsonResponse')->andReturn([]);
        $responseHandler->shouldReceive('responseOutput');

        $jsonData = new class ($this->mock(AlbumRepositoryInterface::class), $folderRepository, $this->fields(), $this->mock(SongRepositoryInterface::class)) extends OpenSubsonic_Json_Data {
            public string $called = '';

            public function addIndexes(array $response, array $artists, int $lastModified = 0): array
            {
                $this->called = 'addIndexes';

                return $response;
            }

            public function addFolderIndexes(array $response, array $children, int $lastModified = 0): array
            {
                $this->called = 'addFolderIndexes';

                return $response;
            }
        };

        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(42);

        $subject = new OpenSubsonicBrowsingHandler(
            $this->mock(AlbumRepositoryInterface::class),
            $this->mock(ArtistRepositoryInterface::class),
            $folderRepository,
            $resolver,
            $jsonData,
            $this->mock(OpenSubsonic_Xml_Data::class),
            $this->mock(Random::class),
            $responseHandler,
            $this->mock(SongRepositoryInterface::class),
            $this->mock(SonicAnalysisPluginResolverInterface::class),
        );

        $subject->getindexes(['f' => 'json'], $user);

        return $jsonData->called;
    }

    /**
     * Runs `getindexes` against a library whose folder tree holds `$children`, and reports which
     * serialiser the handler reached for.
     *
     * @param array<int, array<string, mixed>> $children
     */
    private function serialiserFor(array $children): string
    {
        $this->stubContainer();

        $folderRepository = $this->mock(FolderRepositoryInterface::class);
        $folderRepository->shouldReceive('getCatalogRootChildren')->andReturn($children);

        $resolver = $this->mock(MusicFolderResolverInterface::class);
        $resolver->shouldReceive('musicFolders')->andReturn([3, 0]);

        $responseHandler = $this->mock(SubsonicResponseHandlerInterface::class);
        $responseHandler->shouldReceive('addJsonResponse')->andReturn([]);
        $responseHandler->shouldReceive('responseOutput');

        $jsonData = new class ($this->mock(AlbumRepositoryInterface::class), $folderRepository, $this->mock(SongRepositoryInterface::class), $this->fields()) extends Subsonic_Json_Data {
            public string $called = '';

            public function addIndexes(array $response, array $artists, int $lastModified = 0): array
            {
                $this->called = 'addIndexes';

                return $response;
            }

            public function addFolderIndexes(array $response, array $children, int $lastModified = 0): array
            {
                $this->called = 'addFolderIndexes';

                return $response;
            }
        };

        $subject = new BrowsingHandler(
            $this->mock(AlbumRepositoryInterface::class),
            $this->mock(ArtistRepositoryInterface::class),
            $folderRepository,
            $resolver,
            $this->mock(Random::class),
            $responseHandler,
            $this->mock(SongRepositoryInterface::class),
            $jsonData,
            $this->mock(Subsonic_Xml_Data::class),
        );

        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(42);

        $subject->getindexes(['f' => 'json'], $user);

        return $jsonData->called;
    }

    /**
     * `Catalog` reaches its repositories through the global container, so both statics are answered here
     */
    private function stubContainer(): void
    {
        $catalogRepository = $this->mock(CatalogRepositoryInterface::class);
        // a catalog that will not resolve must be skipped, not end the loop
        $catalogRepository->shouldReceive('findType')->andReturn('');

        $artistRepository = $this->mock(ArtistRepositoryInterface::class);
        $artistRepository->shouldReceive('getArrayRowsByCatalogs')->andReturn(self::ARTISTS);

        $dic = $this->mock(Container::class);
        $dic->shouldReceive('get')->andReturnUsing(fn(string $id): object => match ($id) {
            CatalogRepositoryInterface::class => $catalogRepository,
            ArtistRepositoryInterface::class => $artistRepository,
            default => $this->mock(LoggerInterface::class),
        });
        $GLOBALS['dic'] = $dic;
    }
}
