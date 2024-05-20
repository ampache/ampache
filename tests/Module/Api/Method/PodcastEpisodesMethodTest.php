<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PodcastEpisodesMethodTest extends TestCase
{
    private ModelFactoryInterface&MockObject $modelFactory;

    private PodcastRepositoryInterface&MockObject $podcastRepository;

    private ConfigContainerInterface&MockObject $configContainer;

    private PodcastEpisodesMethod $subject;

    private GatekeeperInterface&MockObject $gatekeeper;

    private ResponseInterface&MockObject $response;

    private ApiOutputInterface&MockObject $output;

    private User $user;

    protected function setUp(): void
    {
        $this->modelFactory      = $this->createMock(ModelFactoryInterface::class);
        $this->podcastRepository = $this->createMock(PodcastRepositoryInterface::class);
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new PodcastEpisodesMethod(
            $this->modelFactory,
            $this->podcastRepository,
            $this->configContainer,
        );

        $this->gatekeeper = $this->createMock(GatekeeperInterface::class);
        $this->response   = $this->createMock(ResponseInterface::class);
        $this->output     = $this->createMock(ApiOutputInterface::class);
        $this->user       = $this->createMock(User::class);
    }

    public function testHandleThrowsIfPodcastsDisabled(): void
    {
        $stream = $this->createMock(StreamInterface::class);

        $result = 'some-error';

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn(0);

        $this->response->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('write')
            ->with($result);

        $this->output->expects(static::once())
            ->method('error')
            ->with(
                ErrorCodeEnum::ACCESS_DENIED,
                'Enable: podcast',
                PodcastEpisodesMethod::ACTION,
                'system'
            )
            ->willReturn($result);

        static::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [],
                $this->user
            )
        );
    }

    public function testHandleThrowsIfPodcastWasNotFound(): void
    {
        static::expectException(RequestParamMissingException::class);
        static::expectExceptionMessage(sprintf('Bad Request: %s', 'filter'));

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        static::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [],
                $this->user
            )
        );
    }

    public function testHandleThrowsIfPodcastIsNew(): void
    {
        $podcastId = 666;

        static::expectException(ResultEmptyException::class);
        static::expectExceptionMessage((string) $podcastId);

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn(null);

        static::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [
                    'filter' => (string) $podcastId,
                ],
                $this->user
            )
        );
    }

    public function testHandleReturnsEmptyResultIfEmpty(): void
    {
        $podcast = $this->createMock(Podcast::class);
        $stream  = $this->createMock(StreamInterface::class);

        $podcastId = 666;
        $result    = '';

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn($podcast);

        $this->response->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('write')
            ->with($result);

        $this->output->expects(static::once())
            ->method('writeEmpty')
            ->with('podcast_episode')
            ->willReturn($result);

        static::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [
                    'filter' => (string) $podcastId,
                ],
                $this->user
            )
        );
    }

    public function testHandleReturnsResult(): void
    {
        $stream  = $this->createMock(StreamInterface::class);
        $podcast = $this->createMock(Podcast::class);

        $result    = '';
        $podcastId = 666;
        $episodeId = 42;
        $limit     = 123;
        $offset    = 456;

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->response->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('write')
            ->with($result);

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn($podcast);

        $this->output->expects(static::once())
            ->method('setOffset')
            ->with($offset);
        $this->output->expects(static::once())
            ->method('setLimit')
            ->with($limit);
        $this->output->expects(static::once())
            ->method('podcastEpisodes')
            ->with([$episodeId], $this->user)
            ->willReturn($result);

        static::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [
                    'filter' => (string) $podcastId,
                    'limit' => (string) $limit,
                    'offset' => (string) $offset,
                ],
                $this->user
            )
        );
    }
}
