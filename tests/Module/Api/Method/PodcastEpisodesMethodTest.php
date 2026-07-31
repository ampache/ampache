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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\User;
use Ampache\Repository\PodcastRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PodcastEpisodesMethodTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private GatekeeperInterface&MockObject $gatekeeper;
    private ModelFactoryInterface&MockObject $modelFactory;
    private ApiOutputInterface&MockObject $output;
    private PodcastRepositoryInterface&MockObject $podcastRepository;
    private ResponseInterface&MockObject $response;
    private PodcastEpisodesMethod $subject;
    private User $user;

    /**
     * The same method serves both api versions; the version is only handed to the output
     *
     * @return array<string, array{0: int}>
     */
    public static function apiVersionProvider(): array
    {
        return [
            'api6' => [6],
            'api8' => [8],
        ];
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleLimitReturnsResponse(int $apiVersion): void
    {
        $stream  = $this->createMock(StreamInterface::class);
        $podcast = $this->createMock(Podcast::class);
        $browse  = $this->createMock(Browse::class);

        $podcastId = 666;
        $episodes  = [1, 2, 3, 4, 5];
        $result    = '';

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->response->expects(static::once())
            ->method('getBody')
            ->willReturn($stream);

        $this->modelFactory->expects(static::once())
            ->method('createBrowse')
            ->with(null, false)
            ->willReturn($browse);

        $browse->expects(static::once())
            ->method('set_user_id')
            ->with($this->user);
        $browse->expects(static::once())
            ->method('set_type')
            ->with('podcast_episode');
        $browse->expects(static::once())
            ->method('set_sort_order')
            ->with('', ['pubdate', 'DESC']);
        $browse->expects(static::once())
            ->method('set_filter')
            ->with('podcast', $podcastId);
        $browse->expects(static::once())
            ->method('set_conditions')
            ->with('');
        $browse->expects(static::once())
            ->method('get_objects')
            ->willReturn($episodes);

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn($podcast);

        $stream->expects(static::once())
            ->method('write')
            ->with($result);

        $this->output->expects(static::once())
            ->method('setOffset')
            ->with($apiVersion, 0);

        $this->output->expects(static::once())
            ->method('setLimit')
            ->with($apiVersion, 1);

        $this->output->expects(static::once())
            ->method('setCount')
            ->with($apiVersion, 5);

        $this->output->expects(static::once())
            ->method('podcastEpisodes')
            ->with($apiVersion, $episodes, $this->user, 'string')
            ->willReturn($result);

        self::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [
                    'filter' => (string) $podcastId,
                    'auth' => 'string',
                    'limit' => '1',
                    'api_format' => 'xml'
                ],
                $this->user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsEmptyResultIfEmpty(int $apiVersion): void
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
            ->with($apiVersion, 'podcast_episode')
            ->willReturn($result);

        self::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [
                    'filter' => (string) $podcastId,
                    'auth' => 'string',
                    'api_format' => 'xml'
                ],
                $this->user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleReturnsResult(int $apiVersion): void
    {
        $stream  = $this->createMock(StreamInterface::class);
        $podcast = $this->createMock(Podcast::class);
        $browse  = $this->createMock(Browse::class);
        $user    = $this->createMock(User::class);

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

        $this->modelFactory->expects(static::once())
            ->method('createBrowse')
            ->with(null, false)
            ->willReturn($browse);

        $browse->expects(static::once())
            ->method('set_user_id')
            ->with($user);
        $browse->expects(static::once())
            ->method('set_type')
            ->with('podcast_episode');
        $browse->expects(static::once())
            ->method('set_type')
            ->with('podcast_episode');
        $browse->expects(static::once())
            ->method('set_sort_order')
            ->with('', ['pubdate', 'DESC']);
        $browse->expects(static::once())
            ->method('set_conditions')
            ->with('');
        $browse->expects(static::once())
            ->method('get_objects')
            ->with()
            ->willReturn([$episodeId]);

        $stream->expects(static::once())
            ->method('write')
            ->with('');

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn($podcast);

        $this->output->expects(static::once())
            ->method('podcastEpisodes')
            ->with($apiVersion, [$episodeId], $this->user, 'string')
            ->willReturn($result);

        self::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [
                    'filter' => (string) $podcastId,
                    'auth' => 'string',
                    'limit' => (string) $limit,
                    'offset' => (string) $offset,
                    'api_format' => 'xml'
                ],
                $this->user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfPodcastIsNew(int $apiVersion): void
    {
        $podcastId = 666;

        static::expectException(RequestParamMissingException::class);
        static::expectExceptionMessage('Bad Request: filter');

        $this->configContainer->expects(static::once())
            ->method('get')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->willReturn('1');

        $this->podcastRepository->expects(static::once())
            ->method('findById')
            ->with($podcastId)
            ->willReturn(null);

        self::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [
                    'filter' => (string) $podcastId,
                    'auth' => 'string',
                    'api_format' => 'xml'
                ],
                $this->user,
                $apiVersion
            )
        );
    }

    #[DataProvider(methodName: 'apiVersionProvider')]
    public function testHandleThrowsIfPodcastsDisabled(int $apiVersion): void
    {
        $podcastId = 666;

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
                $apiVersion,
                ErrorCodeEnum::ACCESS_DENIED,
                'Enable: podcast',
                PodcastEpisodesMethod::ACTION,
                'system'
            )
            ->willReturn($result);

        self::assertSame(
            $this->response,
            $this->subject->handle(
                $this->gatekeeper,
                $this->response,
                $this->output,
                [
                    'filter' => (string) $podcastId,
                    'auth' => 'string',
                    'api_format' => 'xml'
                ],
                $this->user,
                $apiVersion
            )
        );
    }

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
}
