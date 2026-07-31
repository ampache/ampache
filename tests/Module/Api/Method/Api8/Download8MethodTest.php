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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;

class Download8MethodTest extends MockeryTestCase
{
    private FunctionCheckerInterface|MockInterface|null $functionChecker;
    private LibraryItemLoaderInterface|MockInterface|null $libraryItemLoader;
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?Download8Method $subject;
    private ZipHandlerInterface|MockInterface|null $zipHandler;

    public function testHandleIgnoresZipWhenTypeIsNotZipable(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);

        $userId = 666;
        $songId = 42;
        $url    = 'http://ampache.local/play/index.php?action=play&song=42';

        $this->zipHandler->shouldReceive('isZipable')
            ->with('song')
            ->once()
            ->andReturnFalse();

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createSong')
            ->with($songId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('play_url')
            ->with('&client=api&action=download', 'api', false, $userId, $user->streamtoken)
            ->once()
            ->andReturn($url);

        $response->shouldReceive('withStatus')
            ->with(302)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Location', $url)
            ->once()
            ->andReturnSelf();

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => (string) $songId, 'type' => 'song', 'zip' => '1'],
                $user,
                8
            )
        );
    }

    public function testHandleRedirectsToPodcastEpisodeStreamUrl(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $episode    = $this->mock(Podcast_Episode::class);

        $userId    = 666;
        $episodeId = 42;
        $url       = 'http://ampache.local/play/index.php?action=play&podcast_episode=42';

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($episodeId)
            ->once()
            ->andReturn($episode);

        $episode->shouldReceive('play_url')
            ->with('&client=api&action=download', 'api', false, $userId, $user->streamtoken)
            ->once()
            ->andReturn($url);

        $response->shouldReceive('withStatus')
            ->with(302)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Location', $url)
            ->once()
            ->andReturnSelf();

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => (string) $episodeId, 'type' => 'podcast_episode'],
                $user,
                8
            )
        );
    }

    public function testHandleRedirectsToSongStreamUrl(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);

        $userId = 666;
        $songId = 42;
        $url    = 'http://ampache.local:443/play/index.php?action=play&song=42';

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createSong')
            ->with($songId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('play_url')
            ->with('&client=api&action=download', 'api', false, $userId, $user->streamtoken)
            ->once()
            ->andReturn($url);

        $response->shouldReceive('withStatus')
            ->with(302)
            ->once()
            ->andReturnSelf();
        $response->shouldReceive('withHeader')
            ->with('Location', 'http://ampache.local/play/index.php?action=play&song=42')
            ->once()
            ->andReturnSelf();

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => (string) $songId, 'type' => 'song'],
                $user,
                8
            )
        );
    }

    public function testHandleThrowsExceptionIfFilterAndTypeAreMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->expectException(RequestParamMissingException::class);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [],
            $user,
            8
        );
    }

    public function testHandleThrowsExceptionIfSongUrlIsEmpty(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);

        $userId = 666;
        $songId = 42;

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createSong')
            ->with($songId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('play_url')
            ->with('&client=api&action=download', 'api', false, $userId, $user->streamtoken)
            ->once()
            ->andReturn('');

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $songId);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $songId, 'type' => 'song'],
            $user,
            8
        );
    }

    public function testHandleZipThrowsAccessDeniedExceptionIfBatchDownloadIsDisallowed(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $albumId = 42;

        $this->zipHandler->shouldReceive('isZipable')
            ->with('album')
            ->once()
            ->andReturnTrue();

        $this->functionChecker->shouldReceive('check')
            ->with(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD)
            ->once()
            ->andReturnFalse();

        $this->expectException(AccessDeniedException::class);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $albumId, 'type' => 'album', 'zip' => '1'],
            $user,
            8
        );
    }

    public function testHandleZipThrowsResultEmptyExceptionIfContainerIsNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $albumId = 42;

        $this->zipHandler->shouldReceive('isZipable')
            ->with('album')
            ->once()
            ->andReturnTrue();

        $this->functionChecker->shouldReceive('check')
            ->with(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD)
            ->once()
            ->andReturnTrue();

        $this->libraryItemLoader->shouldReceive('load')
            ->with(LibraryItemEnum::ALBUM, $albumId)
            ->once()
            ->andReturnNull();

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $albumId);

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $albumId, 'type' => 'album', 'zip' => '1'],
            $user,
            8
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->modelFactory      = $this->mock(ModelFactoryInterface::class);
        $this->libraryItemLoader = $this->mock(LibraryItemLoaderInterface::class);
        $this->zipHandler        = $this->mock(ZipHandlerInterface::class);
        $this->functionChecker   = $this->mock(FunctionCheckerInterface::class);

        $this->subject = new Download8Method(
            $this->modelFactory,
            $this->libraryItemLoader,
            $this->zipHandler,
            $this->functionChecker
        );
    }
}
