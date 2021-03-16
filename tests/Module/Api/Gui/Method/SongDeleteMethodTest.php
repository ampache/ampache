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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Module\Song\Deletion\SongDeleterInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class SongDeleteMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var MediaDeletionCheckerInterface|MockInterface|null */
    private MockInterface $mediaDeletionChecker;

    /** @var SongDeleterInterface|MockInterface|null */
    private MockInterface $songDeleter;

    /** @var UpdateInfoRepositoryInterface|MockInterface|null */
    private MockInterface $updateInfoRepository;

    private ?SongDeleteMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory        = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory         = $this->mock(ModelFactoryInterface::class);
        $this->mediaDeletionChecker = $this->mock(MediaDeletionCheckerInterface::class);
        $this->songDeleter          = $this->mock(SongDeleterInterface::class);
        $this->updateInfoRepository = $this->mock(UpdateInfoRepositoryInterface::class);

        $this->subject = new SongDeleteMethod(
            $this->streamFactory,
            $this->modelFactory,
            $this->mediaDeletionChecker,
            $this->songDeleter,
            $this->updateInfoRepository
        );
    }

    public function testHandleThrowsExceptionIfFilterParameterIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfSongDoesNotExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
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
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleThrowsExceptionIfDeletionIsNotAllowed(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $objectId = 666;
        $userId   = 42;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 75');

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($song, $userId)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleThrowsExceptionIfDeletionWasNotSuccessful(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $objectId = 666;
        $userId   = 42;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %d', $objectId));

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($objectId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($song, $userId)
            ->once()
            ->andReturnTrue();

        $this->songDeleter->shouldReceive('delete')
            ->with($song)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleReturnsResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId = 666;
        $userId   = 42;
        $result   = 'some-result';

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createSong')
            ->with($objectId)
            ->once()
            ->andReturn($song);

        $song->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($objectId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($song, $userId)
            ->once()
            ->andReturnTrue();

        $this->songDeleter->shouldReceive('delete')
            ->with($song)
            ->once()
            ->andReturnTrue();

        $this->updateInfoRepository->shouldReceive('updateCountByTableName')
            ->with('song')
            ->once();

        $response->shouldReceive('withBody')
            ->with($stream)
            ->once()
            ->andReturnSelf();

        $output->shouldReceive('success')
            ->with(sprintf('song %d deleted', $objectId))
            ->once()
            ->andReturn($result);

        $this->streamFactory->shouldReceive('createStream')
            ->with($result)
            ->once()
            ->andReturn($stream);

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => (string) $objectId]
            )
        );
    }
}
