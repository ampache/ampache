<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Plugin\Adapter\UserMediaPlaySaverAdapterInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class ScrobbleMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var UserRepositoryInterface|MockInterface|null */
    private MockInterface $userRepository;

    /** @var UserMediaPlaySaverAdapterInterface|MockInterface|null */
    private MockInterface $userMediaPlaySaverAdapter;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var LoggerInterface|MockInterface|null */
    private MockInterface $logger;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    /** @var SongRepositoryInterface|MockInterface|null */
    private MockInterface $songRepository;

    private ScrobbleMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory             = $this->mock(StreamFactoryInterface::class);
        $this->userRepository            = $this->mock(UserRepositoryInterface::class);
        $this->userMediaPlaySaverAdapter = $this->mock(UserMediaPlaySaverAdapterInterface::class);
        $this->modelFactory              = $this->mock(ModelFactoryInterface::class);
        $this->logger                    = $this->mock(LoggerInterface::class);
        $this->configContainer           = $this->mock(ConfigContainerInterface::class);
        $this->ui                        = $this->mock(UiInterface::class);
        $this->songRepository            = $this->mock(SongRepositoryInterface::class);

        $this->subject = new ScrobbleMethod(
            $this->streamFactory,
            $this->userRepository,
            $this->userMediaPlaySaverAdapter,
            $this->modelFactory,
            $this->logger,
            $this->configContainer,
            $this->ui,
            $this->songRepository
        );
    }

    /**
     * @dataProvider requestParamDataProvider
     */
    public function testHandleThrowsExceptionIfRequestParamsMissing(
        array $input,
        string $keyName
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $keyName));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            $input
        );
    }

    public function requestParamDataProvider(): array
    {
        return [
            [[], 'song'],
            [['song' => 1], 'artist'],
            [['song' => 1, 'artist' => 1], 'album']
        ];
    }

    public function testHandleThrowsExceptionIfUserIsNotValid(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $userId = 666;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %s', $userId));

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->userRepository->shouldReceive('getValid')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'song' => 1,
                'artist' => 1,
                'album' => 1,
            ]
        );
    }

    public function testHandleThrowsExceptionIfVitalInformationMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $userId     = 666;
        $charset    = 'some-charset';
        $songName   = '';
        $artistName = '';
        $albumName  = '';
        $songMbid   = 'some-song-mbid';
        $artistMbid = 'some-artist-mbid';
        $albumMbId  = 'some-album-mbid';
        $date       = 123456;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request');

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->userRepository->shouldReceive('getValid')
            ->withNoArgs()
            ->once()
            ->andReturn([$userId]);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SITE_CHARSET)
            ->once()
            ->andReturn($charset);

        $this->ui->shouldReceive('scrubOut')
            ->with($songName)
            ->once()
            ->andReturn($songName);
        $this->ui->shouldReceive('scrubIn')
            ->with($artistName)
            ->once()
            ->andReturn($artistName);
        $this->ui->shouldReceive('scrubIn')
            ->with($albumName)
            ->once()
            ->andReturn($albumName);
        $this->ui->shouldReceive('scrubIn')
            ->with($songMbid)
            ->once()
            ->andReturn($songMbid);
        $this->ui->shouldReceive('scrubIn')
            ->with($artistMbid)
            ->once()
            ->andReturn($artistMbid);
        $this->ui->shouldReceive('scrubIn')
            ->with($albumMbId)
            ->once()
            ->andReturn($albumMbId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'song' => $songName,
                'artist' => $artistName,
                'album' => $albumName,
                'song_mbid' => $songMbid,
                'artist_mbid' => $artistMbid,
                'album_mbid' => $albumMbId,
                'date' => $date,
            ]
        );
    }

    public function testHandleThrowsExceptionIfSongWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);

        $userId     = 666;
        $charset    = 'some-charset';
        $songName   = 'some-song-name';
        $artistName = 'some-artist-name';
        $albumName  = 'some-album-name';
        $songMbid   = 'some-song-mbid';
        $artistMbid = 'some-artist-mbid';
        $albumMbId  = 'some-album-mbid';
        $date       = 123456;
        $scrobbleId = 42;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %s', $scrobbleId));

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->userRepository->shouldReceive('getValid')
            ->withNoArgs()
            ->once()
            ->andReturn([$userId]);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SITE_CHARSET)
            ->once()
            ->andReturn($charset);

        $this->ui->shouldReceive('scrubOut')
            ->with($songName)
            ->once()
            ->andReturn($songName);
        $this->ui->shouldReceive('scrubIn')
            ->with($artistName)
            ->once()
            ->andReturn($artistName);
        $this->ui->shouldReceive('scrubIn')
            ->with($albumName)
            ->once()
            ->andReturn($albumName);
        $this->ui->shouldReceive('scrubIn')
            ->with($songMbid)
            ->once()
            ->andReturn($songMbid);
        $this->ui->shouldReceive('scrubIn')
            ->with($artistMbid)
            ->once()
            ->andReturn($artistMbid);
        $this->ui->shouldReceive('scrubIn')
            ->with($albumMbId)
            ->once()
            ->andReturn($albumMbId);

        $this->songRepository->shouldReceive('canScrobble')
            ->with(
                $songName,
                $artistName,
                $albumName,
                $songMbid,
                $artistMbid,
                $albumMbId
            )
            ->once()
            ->andReturn($scrobbleId);

        $this->modelFactory->shouldReceive('createSong')
            ->with($scrobbleId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'song' => $songName,
                'artist' => $artistName,
                'album' => $albumName,
                'song_mbid' => $songMbid,
                'artist_mbid' => $artistMbid,
                'album_mbid' => $albumMbId,
                'date' => $date,
            ]
        );
    }

    public function testHandleScrobbles(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $userId     = 666;
        $charset    = 'some-charset';
        $songName   = 'some-song-name';
        $artistName = 'some-artist-name';
        $albumName  = 'some-album-name';
        $songMbid   = 'some-song-mbid';
        $artistMbid = 'some-artist-mbid';
        $albumMbId  = 'some-album-mbid';
        $date       = 123456;
        $scrobbleId = 42;
        $userName   = 'some-user-name';
        $result     = 'some-result';

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $user->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->userRepository->shouldReceive('getValid')
            ->withNoArgs()
            ->once()
            ->andReturn([$userId]);

        $this->configContainer->shouldReceive('get')
            ->with(ConfigurationKeyEnum::SITE_CHARSET)
            ->once()
            ->andReturn($charset);

        $this->ui->shouldReceive('scrubOut')
            ->with($songName)
            ->once()
            ->andReturn($songName);
        $this->ui->shouldReceive('scrubIn')
            ->with($artistName)
            ->once()
            ->andReturn($artistName);
        $this->ui->shouldReceive('scrubIn')
            ->with($albumName)
            ->once()
            ->andReturn($albumName);
        $this->ui->shouldReceive('scrubIn')
            ->with($songMbid)
            ->once()
            ->andReturn($songMbid);
        $this->ui->shouldReceive('scrubIn')
            ->with($artistMbid)
            ->once()
            ->andReturn($artistMbid);
        $this->ui->shouldReceive('scrubIn')
            ->with($albumMbId)
            ->once()
            ->andReturn($albumMbId);

        $this->songRepository->shouldReceive('canScrobble')
            ->with(
                $songName,
                $artistName,
                $albumName,
                $songMbid,
                $artistMbid,
                $albumMbId
            )
            ->once()
            ->andReturn($scrobbleId);

        $this->modelFactory->shouldReceive('createSong')
            ->with($scrobbleId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $user->username = $userName;

        $this->logger->shouldReceive('debug')
            ->with(
                sprintf(
                    'scrobble: %d for %s using %s %d',
                    $scrobbleId,
                    $userName,
                    'api',
                    $date
                ),
                [LegacyLogger::CONTEXT_TYPE => ScrobbleMethod::class]
            )
            ->once();

        $song->shouldReceive('set_played')
            ->with($userId, 'api', [], $date)
            ->once()
            ->andReturnTrue();

        $this->userMediaPlaySaverAdapter->shouldReceive('save')
            ->with($user, $song)
            ->once();

        $output->shouldReceive('success')
            ->with(sprintf('successfully scrobbled: %d', $scrobbleId))
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'song' => $songName,
                    'artist' => $artistName,
                    'album' => $albumName,
                    'song_mbid' => $songMbid,
                    'artist_mbid' => $artistMbid,
                    'album_mbid' => $albumMbId,
                    'date' => $date,
                ]
            )
        );
    }
}
