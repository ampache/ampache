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
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\SearchRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class PlaylistsMethodTest extends MockeryTestCase
{
    /** @var SearchRepositoryInterface|MockInterface|null */
    private MockInterface $searchRepository;

    /** @var StreamFactoryInterface|MockInterface|null */
    private MockInterface $streamFactory;

    /** @var PlaylistRepositoryInterface|MockInterface|null */
    private MockInterface $playlistRepository;

    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    private ?PlaylistsMethod $subject;

    public function setUp(): void
    {
        $this->searchRepository   = $this->mock(SearchRepositoryInterface::class);
        $this->streamFactory      = $this->mock(StreamFactoryInterface::class);
        $this->playlistRepository = $this->mock(PlaylistRepositoryInterface::class);
        $this->configContainer    = $this->mock(ConfigContainerInterface::class);

        $this->subject = new PlaylistsMethod(
            $this->searchRepository,
            $this->streamFactory,
            $this->playlistRepository,
            $this->configContainer
        );
    }

    public function testHandleReturnsEmptyListIfNothingWasFound(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

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

        $this->playlistRepository->shouldReceive('getPlaylists')
            ->with(
                $userId,
                '',
                true
            )
            ->once()
            ->andReturn([]);
        $this->searchRepository->shouldReceive('getSmartlists')
            ->with(
                $userId,
                '',
                true
            )
            ->once()
            ->andReturn([]);

        $output->shouldReceive('emptyResult')
            ->with('playlist')
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
                []
            )
        );
    }

    public function testHandleReturnsPlaylists(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $stream     = $this->mock(StreamInterface::class);

        $result      = 'some-result';
        $playlistIds = [1, 2];
        $filterValue = 'some-filter';
        $userId      = 666;
        $limit       = 21;
        $offset      = 42;

        $gatekeeper->shouldReceive('getUser->getId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->playlistRepository->shouldReceive('getPlaylists')
            ->with(
                $userId,
                $filterValue,
                false
            )
            ->once()
            ->andReturn($playlistIds);

        $output->shouldReceive('playlists')
            ->with(
                $playlistIds,
                $userId,
                false,
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
                    'exact' => 1,
                    'hide_search' => 1,
                    'filter' => $filterValue,
                    'limit' => (string) $limit,
                    'offset' => (string) $offset,
                ]
            )
        );
    }
}
