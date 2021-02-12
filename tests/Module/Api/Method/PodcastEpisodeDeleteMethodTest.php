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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Catalog\MediaDeletionCheckerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\UpdateInfoRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PodcastEpisodeDeleteMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var MediaDeletionCheckerInterface|MockInterface|null */
    private MockInterface $mediaDeletionChecker;

    /** @var UpdateInfoRepositoryInterface|MockInterface|null */
    private MockInterface $updateInfoRepository;

    private ?PodcastEpisodeDeleteMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory        = $this->mock(StreamFactoryInterface::class);
        $this->configContainer      = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory         = $this->mock(ModelFactoryInterface::class);
        $this->mediaDeletionChecker = $this->mock(MediaDeletionCheckerInterface::class);
        $this->updateInfoRepository = $this->mock(UpdateInfoRepositoryInterface::class);

        $this->subject = new PodcastEpisodeDeleteMethod(
            $this->streamFactory,
            $this->configContainer,
            $this->modelFactory,
            $this->mediaDeletionChecker,
            $this->updateInfoRepository
        );
    }

    public function testHandleThrowsExceptionIfPodcastsAreDisabled(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnFalse();

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: podcast');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfFilterParamIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
        );
    }

    public function testHandleThrowsExceptionIfObjectWasNotFound(): void
    {
        $gatekeeper     = $this->mock(GatekeeperInterface::class);
        $response       = $this->mock(ResponseInterface::class);
        $output         = $this->mock(ApiOutputInterface::class);
        $podcastEpisode = $this->mock(Podcast_Episode::class);

        $objectId = 666;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage(sprintf('Not Found: %d', $objectId));

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($objectId)
            ->once()
            ->andReturn($podcastEpisode);

        $podcastEpisode->shouldReceive('isNew')
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

    public function testHandleThrowsExceptionIfAccessIsDenied(): void
    {
        $gatekeeper     = $this->mock(GatekeeperInterface::class);
        $response       = $this->mock(ResponseInterface::class);
        $output         = $this->mock(ApiOutputInterface::class);
        $podcastEpisode = $this->mock(Podcast_Episode::class);

        $objectId = 666;
        $userId   = 42;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Require: 75');

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($objectId)
            ->once()
            ->andReturn($podcastEpisode);

        $podcastEpisode->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($podcastEpisode, $userId)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleThrowsExceptionIsDeletionFails(): void
    {
        $gatekeeper     = $this->mock(GatekeeperInterface::class);
        $response       = $this->mock(ResponseInterface::class);
        $output         = $this->mock(ApiOutputInterface::class);
        $podcastEpisode = $this->mock(Podcast_Episode::class);

        $objectId = 666;
        $userId   = 42;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %d', $objectId));

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($objectId)
            ->once()
            ->andReturn($podcastEpisode);

        $podcastEpisode->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $podcastEpisode->shouldReceive('remove')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $podcastEpisode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($objectId);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($podcastEpisode, $userId)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId]
        );
    }

    public function testHandleDeletes(): void
    {
        $gatekeeper        = $this->mock(GatekeeperInterface::class);
        $response          = $this->mock(ResponseInterface::class);
        $output            = $this->mock(ApiOutputInterface::class);
        $podcastEpisode    = $this->mock(Podcast_Episode::class);
        $stream            = $this->mock(StreamInterface::class);

        $objectId = 666;
        $userId   = 42;
        $result   = 'some-result';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->modelFactory->shouldReceive('createPodcastEpisode')
            ->with($objectId)
            ->once()
            ->andReturn($podcastEpisode);

        $podcastEpisode->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();
        $podcastEpisode->shouldReceive('remove')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $podcastEpisode->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($objectId);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->mediaDeletionChecker->shouldReceive('mayDelete')
            ->with($podcastEpisode, $userId)
            ->once()
            ->andReturnTrue();

        $this->updateInfoRepository->shouldReceive('updateCountByTableName')
            ->with('podcast_episode')
            ->once();

        $output->shouldReceive('success')
            ->with(sprintf('podcast_episode %d deleted', $objectId))
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
