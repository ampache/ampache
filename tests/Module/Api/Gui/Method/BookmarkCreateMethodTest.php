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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Song;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class BookmarkCreateMethodTest extends MockeryTestCase
{
    /** @var BookmarkRepositoryInterface|MockInterface|null */
    private MockInterface $bookmarkRepository;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    private BookmarkCreateMethod $subject;

    public function setUp(): void
    {
        $this->bookmarkRepository = $this->mock(BookmarkRepositoryInterface::class);
        $this->streamFactory      = $this->mock(StreamFactoryInterface::class);
        $this->configContainer    = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory       = $this->mock(ModelFactoryInterface::class);
        $this->ui                 = $this->mock(UiInterface::class);

        $this->subject = new BookmarkCreateMethod(
            $this->bookmarkRepository,
            $this->streamFactory,
            $this->configContainer,
            $this->modelFactory,
            $this->ui
        );
    }

    public function testHandleThrowsExceptionIfFilterParamIsMissing(): void
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

    public function testHandleThrowsExceptionIfPositionParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: position');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => 666]
        );
    }

    public function testHandleThrowsExceptionIfTypeIsNotAllowed(): void
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
            ['filter' => 666, 'position' => 42]
        );
    }

    public function testHandleThrowsExceptionIfTypeIsVideoAndVideoIsNotAllowed(): void
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
            ['filter' => 666, 'position' => 42, 'type' => 'video']
        );
    }

    public function testHandleThrowsExceptionIfObjectDoesNotExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $objectId   = 666;
        $objectType = 'podcast_episode';

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($objectType, $objectId)
            ->once()
            ->andReturnNull();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId, 'position' => 42, 'type' => $objectType]
        );
    }

    public function testHandleAddBookmark(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId   = 666;
        $objectType = 'podcast_episode';
        $comment    = 'some-comment';
        $userId     = 42;
        $position   = 33;
        $bookmarkId = 111;
        $result     = 'some-result';
        $time       = 222;

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($objectType, $objectId)
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

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->bookmarkRepository->shouldReceive('create')
            ->with(
                $position,
                $comment,
                $objectType,
                $objectId,
                $userId,
                $time
            )
            ->once()
            ->andReturn($bookmarkId);

        $this->ui->shouldReceive('scrubIn')
            ->with('AmpacheAPI')
            ->once()
            ->andReturn($comment);

        $output->shouldReceive('bookmarks')
            ->with([$bookmarkId])
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
                    'filter' => (string) $objectId,
                    'position' => (string) $position,
                    'type' => $objectType,
                    'date' => (string) $time,
                ]
            )
        );
    }
}
