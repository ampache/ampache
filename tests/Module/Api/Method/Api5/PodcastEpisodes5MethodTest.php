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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PodcastEpisodes5MethodTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private GatekeeperInterface&MockObject $gatekeeper;
    private ApiOutputInterface&MockObject $output;
    private PodcastRepositoryInterface&MockObject $podcastRepository;
    private ResponseInterface&MockObject $response;
    private PodcastEpisodes5Method $subject;
    private User&MockObject $user;

    public function testHandleReturnsEmptyResultIfEmpty(): void
    {
        $podcastId = 42;
        $result    = 'empty-result';

        $podcast = $this->createMock(Podcast::class);
        $stream  = $this->createMock(StreamInterface::class);

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn($podcast);

        $podcast->expects(static::once())
            ->method('getEpisodeIds')
            ->willReturn([]);

        $this->response->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('write')
            ->with($result);

        $this->output->expects(static::once())
            ->method('writeEmpty')
            ->with(5, 'podcast_episode')
            ->willReturn($result);

        /** @noinspection PhpMissingArrayKeyInspection */
        self::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                ['filter' => (string) $podcastId],
                $this->user,
                5
            )
        );
    }

    public function testHandleReturnsResult(): void
    {
        $podcastId = 42;
        $episodeId = 7;
        $result    = 'some-result';

        $podcast = $this->createMock(Podcast::class);
        $stream  = $this->createMock(StreamInterface::class);

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn($podcast);

        $podcast->expects(static::once())
            ->method('getEpisodeIds')
            ->willReturn([$episodeId]);

        $this->output->expects(static::once())
            ->method('setOffset')
            ->with(5, 0);

        $this->output->expects(static::once())
            ->method('setLimit')
            ->with(5, 0);

        $this->response->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $stream->expects(static::once())
            ->method('write')
            ->with($result);

        $this->output->expects(static::once())
            ->method('podcastEpisodes')
            ->with(5, [$episodeId], $this->user, 'string')
            ->willReturn($result);

        /** @noinspection PhpMissingArrayKeyInspection */
        self::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                ['filter' => (string) $podcastId, 'auth' => 'string'],
                $this->user,
                5
            )
        );
    }

    public function testHandleThrowsIfFilterIsMissing(): void
    {
        static::expectException(RequestParamMissingException::class);
        static::expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'filter'));

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            [],
            $this->user,
            5
        );
    }

    public function testHandleThrowsIfPodcastsNotEnabled(): void
    {
        static::expectException(AccessDeniedException::class);
        static::expectExceptionMessage('Enable: podcast');

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('');

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            [],
            $this->user,
            5
        );
    }

    public function testHandleThrowsIfPodcastWasNotFound(): void
    {
        $podcastId = 42;

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

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $this->gatekeeper,
            $this->response,
            $this->output,
            ['filter' => (string) $podcastId],
            $this->user,
            5
        );
    }

    protected function setUp(): void
    {
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);
        $this->podcastRepository = $this->createMock(PodcastRepositoryInterface::class);

        $this->subject = new PodcastEpisodes5Method(
            $this->configContainer,
            $this->podcastRepository,
        );

        $this->gatekeeper = $this->createMock(GatekeeperInterface::class);
        $this->response   = $this->createMock(ResponseInterface::class);
        $this->output     = $this->createMock(ApiOutputInterface::class);
        $this->user       = $this->createMock(User::class);
    }
}
