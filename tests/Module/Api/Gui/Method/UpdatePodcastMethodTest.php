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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class UpdatePodcastMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var MockInterface|PodcastSyncerInterface */
    private MockInterface $podcastSyncer;

    /** @var MockInterface|PodcastRepositoryInterface */
    private MockInterface $podcastRepository;

    private UpdatePodcastMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory     = $this->mock(StreamFactoryInterface::class);
        $this->podcastSyncer     = $this->mock(PodcastSyncerInterface::class);
        $this->podcastRepository = $this->mock(PodcastRepositoryInterface::class);

        $this->subject = new UpdatePodcastMethod(
            $this->streamFactory,
            $this->podcastSyncer,
            $this->podcastRepository
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

    public function testHandleThrowsExceptionIfAccessIsDenied(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 50');

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => 666]
        );
    }

    public function testHandleThrowsExceptionIfPodcastWasNotFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $podcast    = $this->mock(Podcast::class);

        $objectId = 666;

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturnNull();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleThrowsExceptionIfUpdateErrors(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $podcast    = $this->mock(Podcast::class);

        $objectId = 666;

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %d', $objectId));

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturn($podcast);

        $this->podcastSyncer->shouldReceive('sync')
            ->with($podcast, true)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleUpdates(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $podcast    = $this->mock(Podcast::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId = 666;
        $result   = 'some-result';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturn($podcast);

        $this->podcastSyncer->shouldReceive('sync')
            ->with($podcast, true)
            ->once()
            ->andReturnTrue();

        $output->shouldReceive('success')
            ->with(sprintf(T_('Synced episodes for podcast: %d'), $objectId))
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
                ['filter' => (string) $objectId]
            )
        );
    }
}
