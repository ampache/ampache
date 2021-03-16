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

class BookmarkEditMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var BookmarkRepositoryInterface|MockInterface|null */
    private MockInterface $bookmarkRepository;

    /** @var UiInterface|MockInterface|null */
    private MockInterface $ui;

    private BookmarkEditMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory      = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory       = $this->mock(ModelFactoryInterface::class);
        $this->configContainer    = $this->mock(ConfigContainerInterface::class);
        $this->bookmarkRepository = $this->mock(BookmarkRepositoryInterface::class);
        $this->ui                 = $this->mock(UiInterface::class);

        $this->subject = new BookmarkEditMethod(
            $this->streamFactory,
            $this->modelFactory,
            $this->configContainer,
            $this->bookmarkRepository,
            $this->ui
        );
    }

    /**
     * @dataProvider requestDataProvider
     */
    public function testHandleThrowsExceptionIfRequestParamIsMissing(
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

    public function requestDataProvider(): array
    {
        return [
            [[], 'filter'],
            [['filter' => 1], 'position'],
            [['filter' => 1, 'position' => 1], 'type'],
        ];
    }

    public function testHandleThrowsExceptionIfTypeIsVideoAndVideoIsDisabled(): void
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
            ['type' => 'video', 'filter' => 666, 'position' => 42]
        );
    }

    public function testHandleThrowsExceptionIfItemIsNotSupported(): void
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
            ['type' => 'foobar', 'filter' => 666, 'position' => 42]
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

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $this->ui->shouldReceive('scrubIn')
            ->with('AmpacheAPI')
            ->once()
            ->andReturn('');

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $song->id = 0;

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['type' => $type, 'filter' => (string) $objectId, 'position' => 42]
        );
    }

    public function testHandleReturnsEmptyResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId = 666;
        $type     = 'song';
        $result   = 'some-result';
        $comment  = 'some-comment';
        $userId   = 42;

        $this->ui->shouldReceive('scrubIn')
            ->with('AmpacheAPI')
            ->once()
            ->andReturn($comment);

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $song->id = $objectId;

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->bookmarkRepository->shouldReceive('lookup')
            ->with($type, $objectId, $userId, $comment)
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
                ['type' => $type, 'filter' => (string) $objectId, 'position' => 42]
            )
        );
    }

    public function testHandleUpdatesAndReturnsResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $song       = $this->mock(Song::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId    = 666;
        $type        = 'song';
        $result      = 'some-result';
        $comment     = 'some-comment';
        $userId      = 42;
        $bookmarkIds = [1, 2];
        $position    = 33;
        $time        = 111;

        $this->ui->shouldReceive('scrubIn')
            ->with('AmpacheAPI')
            ->once()
            ->andReturn($comment);

        $this->modelFactory->shouldReceive('mapObjectType')
            ->with($type, $objectId)
            ->once()
            ->andReturn($song);

        $song->id = $objectId;

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->bookmarkRepository->shouldReceive('lookup')
            ->with($type, $objectId, $userId, $comment)
            ->once()
            ->andReturn($bookmarkIds);
        $this->bookmarkRepository->shouldReceive('edit')
            ->with(
                $position,
                $comment,
                $type,
                $objectId,
                $userId,
                $time
            )
            ->once();

        $output->shouldReceive('bookmarks')
            ->with($bookmarkIds)
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
                    'type' => $type,
                    'filter' => (string) $objectId,
                    'position' => $position,
                    'date' => $time
                ]
            )
        );
    }
}
