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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class BookmarkDeleteMethodTest extends MockeryTestCase
{
    /** @var BookmarkRepositoryInterface|MockInterface|null */
    private MockInterface $bookmarkRepository;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    private ?BookmarkDeleteMethod $subject;

    public function setUp(): void
    {
        $this->bookmarkRepository = $this->mock(BookmarkRepositoryInterface::class);
        $this->modelFactory       = $this->mock(ModelFactoryInterface::class);
        $this->streamFactory      = $this->mock(StreamFactoryInterface::class);
        $this->configContainer    = $this->mock(ConfigContainerInterface::class);
        $this->ui                 = $this->mock(UiInterface::class);

        $this->subject = new BookmarkDeleteMethod(
            $this->bookmarkRepository,
            $this->modelFactory,
            $this->streamFactory,
            $this->configContainer,
            $this->ui
        );
    }

    public function testHandleThrowsExceptionIfFilterIsMissing(): void
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

    public function testHandleThrowsExceptionIfTypeIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: type');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => 666]
        );
    }

    public function testHandleThrowsExceptionIfTypeVideoAndVideoDisabled(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: video');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALLOW_VIDEO)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => 666, 'type' => 'video']
        );
    }

    public function testHandleThrowsExceptionIfTypeIsNotSupported(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => 666, 'type' => 'foobar']
        );
    }

    public function testHandleThrowsExceptionIfItemWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $objectId = 666;
        $type     = 'song';
        $song->id = 0;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId, 'type' => $type]
        );
    }

    public function testHandleReturnsEmptyResultfNoBookmarkWasFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId = 666;
        $type     = 'song';
        $song->id = $objectId;
        $comment  = 'some-comment';
        $userId   = 42;
        $result   = 'some-result';

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->ui->shouldReceive('scrubIn')
            ->with('AmpacheAPI')
            ->once()
            ->andReturn($comment);

        $this->bookmarkRepository->shouldReceive('lookup')
            ->with(
                $type,
                $objectId,
                $userId,
                $comment
            )
            ->once()
            ->andReturn([]);

        $output->shouldReceive('emptyResult')
            ->with('bookmark')
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
                ['filter' => (string) $objectId, 'type' => $type]
            )
        );
    }

    public function testHandleThrowsExceptionIfBookmarkIsNotDeletable(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);

        $objectId   = 666;
        $type       = 'song';
        $song->id   = $objectId;
        $comment    = 'some-comment';
        $userId     = 42;
        $bookmarkId = 33;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->ui->shouldReceive('scrubIn')
            ->with('AmpacheAPI')
            ->once()
            ->andReturn($comment);

        $this->bookmarkRepository->shouldReceive('lookup')
            ->with(
                $type,
                $objectId,
                $userId,
                $comment
            )
            ->once()
            ->andReturn([$bookmarkId]);
        $this->bookmarkRepository->shouldReceive('delete')
            ->with($bookmarkId)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId, 'type' => $type]
        );
    }

    public function testHandleDeletes(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId   = 666;
        $type       = 'song';
        $song->id   = $objectId;
        $comment    = 'some-comment';
        $userId     = 42;
        $result     = 'some-result';
        $bookmarkId = 33;

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->ui->shouldReceive('scrubIn')
            ->with('AmpacheAPI')
            ->once()
            ->andReturn($comment);

        $this->bookmarkRepository->shouldReceive('lookup')
            ->with(
                $type,
                $objectId,
                $userId,
                $comment
            )
            ->once()
            ->andReturn([$bookmarkId]);
        $this->bookmarkRepository->shouldReceive('delete')
            ->with($bookmarkId)
            ->once()
            ->andReturnTrue();

        $output->shouldReceive('success')
            ->with(sprintf('Deleted Bookmark: %d', $objectId))
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
                ['filter' => (string) $objectId, 'type' => $type]
            )
        );
    }
}
