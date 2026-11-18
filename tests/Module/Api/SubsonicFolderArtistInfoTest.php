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
use Ampache\Module\Database\database_object;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\System\Dba;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use DI\Container;
use Psr\Log\LoggerInterface;

/** DSub stopped on "The requested data was not found" for a folder without a single artist (#4556) */
class SubsonicFolderArtistInfoTest extends MockeryTestCase
{
    private const int FOLDER = 55961;

    public function testOpenSubsonicAnswersAnEmptyArtistInfo2ForAFolderWithoutASingleArtist(): void
    {
        $folderRepository = $this->folderRepository();

        $captured        = [];
        $responseHandler = $this->mock(OpenSubsonicResponseHandlerInterface::class);
        $responseHandler->shouldReceive('checkParameter')->andReturn('fo-' . self::FOLDER);
        $responseHandler->shouldReceive('addJsonResponse')->andReturn(['subsonic-response' => []]);
        $responseHandler->shouldReceive('responseOutput')->andReturnUsing(function (array $input, string $function, array $response) use (&$captured): void {
            $captured = $response;
        });
        $responseHandler->shouldNotReceive('errorOutput');

        $fields = new OpenSubsonic_Fields(
            $this->mock(BookmarkRepositoryInterface::class),
            $this->mock(LabelRepositoryInterface::class),
            $this->mock(SongRepositoryInterface::class),
        );

        $subject = new OpenSubsonicBrowsingHandler(
            $this->mock(AlbumRepositoryInterface::class),
            $this->mock(ArtistRepositoryInterface::class),
            $folderRepository,
            $this->mock(OpenSubsonicMusicFolderResolverInterface::class),
            new OpenSubsonic_Json_Data($this->mock(AlbumRepositoryInterface::class), $folderRepository, $fields, $this->mock(SongRepositoryInterface::class)),
            $this->mock(OpenSubsonic_Xml_Data::class),
            $this->mock(Random::class),
            $responseHandler,
            $this->mock(SongRepositoryInterface::class),
            $this->mock(SonicAnalysisPluginResolverInterface::class),
        );

        $subject->getartistinfo2(['id' => 'fo-' . self::FOLDER, 'f' => 'json'], $this->user());

        $info = $captured['subsonic-response']['artistInfo2'] ?? null;
        self::assertIsArray($info, 'DSub stops on an error, it needs an answer to keep browsing');
        self::assertArrayNotHasKey('biography', $info);
        self::assertSame([], $info['similarArtist']);
    }

    public function testSubsonicAnswersAnEmptyArtistInfoForAFolderWithoutASingleArtist(): void
    {
        $folderRepository = $this->folderRepository();

        $captured        = [];
        $responseHandler = $this->mock(SubsonicResponseHandlerInterface::class);
        $responseHandler->shouldReceive('checkParameter')->andReturn('fo-' . self::FOLDER);
        $responseHandler->shouldReceive('addJsonResponse')->andReturn(['subsonic-response' => []]);
        $responseHandler->shouldReceive('responseOutput')->andReturnUsing(function (array $input, string $function, array $response) use (&$captured): void {
            $captured = $response;
        });
        $responseHandler->shouldNotReceive('errorOutput');

        $fields = new OpenSubsonic_Fields(
            $this->mock(BookmarkRepositoryInterface::class),
            $this->mock(LabelRepositoryInterface::class),
            $this->mock(SongRepositoryInterface::class),
        );

        $subject = new BrowsingHandler(
            $this->mock(AlbumRepositoryInterface::class),
            $this->mock(ArtistRepositoryInterface::class),
            $folderRepository,
            $this->mock(MusicFolderResolverInterface::class),
            $this->mock(Random::class),
            $responseHandler,
            $this->mock(SongRepositoryInterface::class),
            new Subsonic_Json_Data($this->mock(AlbumRepositoryInterface::class), $folderRepository, $this->mock(SongRepositoryInterface::class), $fields),
            $this->mock(Subsonic_Xml_Data::class),
        );

        $subject->getartistinfo(['id' => 'fo-' . self::FOLDER, 'f' => 'json'], $this->user());

        $info = $captured['subsonic-response']['artistInfo'] ?? null;
        self::assertIsArray($info, 'DSub stops on an error, it needs an answer to keep browsing');
        self::assertArrayNotHasKey('biography', $info);
        self::assertSame([], $info['similarArtist']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // escaping the folder table name without a connection is logged on the way to the cache
        $logger = $this->mock(LoggerInterface::class);
        $logger->shouldIgnoreMissing();

        $dic = $this->mock(Container::class);
        $dic->shouldReceive('get')->andReturn($logger);
        $GLOBALS['dic'] = $dic;

        // priming the cache keeps `new Folder()` off the database
        database_object::add_to_cache((string) Dba::escape('folder'), self::FOLDER, [
            'id' => self::FOLDER,
            'name' => 'Various',
            'catalog' => 1,
            'parent' => null,
            'path_name' => '/music/various',
            'playable' => 0,
        ]);
    }

    private function folderRepository(): FolderRepositoryInterface
    {
        $folderRepository = $this->mock(FolderRepositoryInterface::class);
        $folderRepository->shouldReceive('resolveSingleArtistId')->andReturnNull();

        return $folderRepository;
    }

    private function user(): User
    {
        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(42);

        return $user;
    }
}
