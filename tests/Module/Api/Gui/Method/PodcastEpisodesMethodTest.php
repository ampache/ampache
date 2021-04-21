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
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PodcastEpisodesMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var MockInterface|PodcastEpisodeRepositoryInterface */
    private MockInterface $podcastEpisodeRepository;

    /** @var MockInterface|PodcastRepositoryInterface */
    private MockInterface $podcastRepository;

    private ?PodcastEpisodesMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory            = $this->mock(StreamFactoryInterface::class);
        $this->configContainer          = $this->mock(ConfigContainerInterface::class);
        $this->podcastEpisodeRepository = $this->mock(PodcastEpisodeRepositoryInterface::class);
        $this->podcastRepository        = $this->mock(PodcastRepositoryInterface::class);

        $this->subject = new PodcastEpisodesMethod(
            $this->streamFactory,
            $this->configContainer,
            $this->podcastEpisodeRepository,
            $this->podcastRepository
        );
    }

    public function testHandleThrowsExceptionIfPodcastIsNotEnabled(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: podcast');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnFalse();

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

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage('Bad Request: filter');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            []
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
        $this->expectExceptionMessage((string) $objectId);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
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

    public function testHandleReturnsEmptyResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $podcast    = $this->mock(Podcast::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId   = 666;
        $result     = 'some-result';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturn($podcast);

        $this->podcastEpisodeRepository->shouldReceive('getEpisodeIds')
            ->with($podcast)
            ->once()
            ->andReturn([]);

        $output->shouldReceive('emptyResult')
            ->with('podcast_episode')
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

    public function testHandleReturnsResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $podcast    = $this->mock(Podcast::class);
        $stream     = $this->mock(StreamInterface::class);

        $objectId   = 666;
        $result     = 'some-result';
        $episodeIds = [1, 3];
        $limit      = 42;
        $offset     = 333;
        $userId     = 33;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($objectId)
            ->once()
            ->andReturn($podcast);

        $this->podcastEpisodeRepository->shouldReceive('getEpisodeIds')
            ->with($podcast)
            ->once()
            ->andReturn($episodeIds);

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $output->shouldReceive('podcast_episodes')
            ->with(
                $episodeIds,
                $userId,
                false,
                true,
                true,
                $limit,
                $offset
            )
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
                ['filter' => (string) $objectId, 'limit' => (string) $limit, 'offset' => (string) $offset]
            )
        );
    }
}
