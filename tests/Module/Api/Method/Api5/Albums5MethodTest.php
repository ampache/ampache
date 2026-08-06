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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class Albums5MethodTest extends MockeryTestCase
{
    private MockInterface|BrowseFactoryInterface|null $browseFactory;
    private MockInterface|StreamFactoryInterface|null $streamFactory;
    private ?Albums5Method $subject;

    public function testHandleReturnsEmptyResultIfEmpty(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result = 'empty-result';

        $this->browseFactory->shouldReceive('create')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('album')
            ->once();
        $browse->shouldReceive('set_sort')
            ->with('name', 'ASC', false)
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('alpha_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('writeEmpty')
            ->with(5, 'album')
            ->once()
            ->andReturn($result);

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);

        $stream->shouldReceive('write')
            ->with($result)
            ->once();

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['auth' => 'stringauth'],
                $user,
                5
            )
        );
    }

    public function testHandleReturnsResponse(): void
    {
        ob_start();

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $album      = $this->mock(Album::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $result  = 'some-result';
        $include = [];

        $this->browseFactory->shouldReceive('create')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('album')
            ->once();
        $browse->shouldReceive('set_sort')
            ->with('name', 'ASC', false)
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('alpha_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([$album]);

        $output->shouldReceive('setOffset')
            ->with(5, 0)
            ->once();
        $output->shouldReceive('setLimit')
            ->with(5, 0)
            ->once();

        $output->shouldReceive('albums')
            ->with(
                5,
                [$album],
                $include,
                $user,
                'stringauth',
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

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                [
                    'include' => $include,
                    'auth' => 'stringauth',
                ],
                $user,
                5
            )
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->browseFactory  = $this->mock(BrowseFactoryInterface::class);
        $this->streamFactory  = $this->mock(StreamFactoryInterface::class);

        $this->subject = new Albums5Method(
            $this->browseFactory,
            $this->streamFactory
        );
    }
}
