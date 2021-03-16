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
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\SearchRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class GetIndexesMethodTest extends MockeryTestCase
{
    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var SearchRepositoryInterface|MockInterface|null */
    private MockInterface $searchRepository;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    private GetIndexesMethod $subject;

    public function setUp(): void
    {
        $this->streamFactory    = $this->mock(StreamFactoryInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);
        $this->searchRepository = $this->mock(SearchRepositoryInterface::class);
        $this->modelFactory     = $this->mock(ModelFactoryInterface::class);

        $this->subject = new GetIndexesMethod(
            $this->streamFactory,
            $this->configContainer,
            $this->searchRepository,
            $this->modelFactory
        );
    }

    public function testHandleThrowsExceptionIfTypeParamIsMissing(): void
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
            []
        );
    }

    /**
     * @dataProvider typeFeatureDataProvider
     */
    public function testHandleThrowsExceptionIfFeatureIsDisabled(
        string $type,
        string $feature
    ): void {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage(sprintf('Enable: %s', $type));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with($feature)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => $type
            ]
        );
    }

    public function typeFeatureDataProvider(): array
    {
        return [
            ['video', ConfigurationKeyEnum::ALLOW_VIDEO],
            ['podcast', ConfigurationKeyEnum::PODCAST],
            ['podcast_episode', ConfigurationKeyEnum::PODCAST],
            ['live_stream', ConfigurationKeyEnum::LIVE_STREAM],
        ];
    }

    public function testHandleThrowsExceptionIfTypeIsNotAllowed(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);

        $type = 'foobar';

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf('Bad Request: %s', $type));

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            [
                'type' => $type
            ]
        );
    }

    public function testHandleWithTypePlaylistAndSmartlistReturnsEmptyList(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $type   = 'playlist';
        $result = 'some-result';
        $userId = 666;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::HIDE_SEARCH)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('reset_filters')
            ->withNoArgs()
            ->once();
        $browse->shouldReceive('set_type')
            ->with($type)
            ->once();
        $browse->shouldReceive('set_sort')
            ->with('name', 'ASC')
            ->once();
        $browse->shouldReceive('set_filter')
            ->with('playlist_type', $userId)
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->searchRepository->shouldReceive('getSmartlists')
            ->with($userId)
            ->once()
            ->andReturn([]);

        $output->shouldReceive('emptyResult')
            ->with($type)
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
                    'type' => $type
                ]
            )
        );
    }

    public function testHandleWithTypePlaylistReturnsEmptyList(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $type   = 'playlist';
        $result = 'some-result';
        $userId = 666;

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('reset_filters')
            ->withNoArgs()
            ->once();
        $browse->shouldReceive('set_type')
            ->with($type)
            ->once();
        $browse->shouldReceive('set_sort')
            ->with('name', 'ASC')
            ->once();
        $browse->shouldReceive('set_filter')
            ->with('playlist_type', $userId)
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('emptyResult')
            ->with($type)
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
                    'hide_search' => 1,
                ]
            )
        );
    }

    public function testHandleWithTypeAlbumArtistReturnsList(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);

        $type        = 'album_artist';
        $result      = 'some-result';
        $userId      = 666;
        $objectId    = 42;
        $limit       = 33;
        $offset      = 21;
        $filterValue = 'some-filter-value';

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('reset_filters')
            ->withNoArgs()
            ->once();
        $browse->shouldReceive('set_type')
            ->with('artist')
            ->once();
        $browse->shouldReceive('set_sort')
            ->with('name', 'ASC')
            ->once();
        $browse->shouldReceive('set_filter')
            ->with('exact_match', $filterValue)
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([(string) $objectId]);

        $output->shouldReceive('indexes')
            ->with(
                [$objectId],
                'artist',
                $userId,
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
                [
                    'type' => $type,
                    'hide_search' => 1,
                    'limit' => (string) $limit,
                    'offset' => (string) $offset,
                    'exact' => 1,
                    'filter' => $filterValue,
                    'include' => 1
                ]
            )
        );
    }
}
