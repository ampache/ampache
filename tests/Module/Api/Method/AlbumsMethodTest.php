<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class AlbumsMethodTest extends MockeryTestCase
{
    /** @var MockInterface|StreamFactoryInterface|null */
    private MockInterface $streamFactory;

    /** @var MockInterface|ModelFactoryInterface|null */
    private MockInterface $modelFactory;

    private AlbumsMethod $subject;

    protected function setUp(): void
    {
        $this->streamFactory = $this->mock(StreamFactoryInterface::class);
        $this->modelFactory  = $this->mock(ModelFactoryInterface::class);

        $this->subject = new AlbumsMethod(
            $this->streamFactory,
            $this->modelFactory
        );
    }

    public function testHandleEmptyListReturnsResponse(): void
    {
        ob_start();

        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $browse     = $this->mock(Browse::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $user->catalogs['music'] = [1];

        $result  = '';
        $include = [];
        $limit   = 0;
        $offset  = 0;

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('album')
            ->once();
        $browse->shouldReceive('set_sort_order')
            ->with('', ['name_year', 'ASC'])
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('exact_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('setOffset')
            ->with($offset)
            ->once();

        $output->shouldReceive('setLimit')
            ->with($limit)
            ->once();

        $browse->shouldReceive('get_total')
            ->withNoArgs()
            ->once()
            ->andReturn(0);
        $output->shouldReceive('setCount')
            ->with(0)
            ->once();

        $output->shouldReceive('albums')
            ->with(
                [],
                $include,
                $user
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
                    'include' => $include,
                    'exact' => true,
                    'api_format' => 'json',
                    'auth' => 'stringauth',
                ],
                $user
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

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('album')
            ->once();
        $browse->shouldReceive('set_sort_order')
            ->with('', ['name_year', 'ASC'])
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('exact_match', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('add', '')
            ->once();
        $browse->shouldReceive('set_api_filter')
            ->with('update', '')
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([$album]);

        $output->shouldReceive('setOffset')
            ->with(0)
            ->once();

        $output->shouldReceive('setLimit')
            ->with(0)
            ->once();

        $browse->shouldReceive('get_total')
            ->withNoArgs()
            ->once()
            ->andReturn(1);
        $output->shouldReceive('setCount')
            ->with(1)
            ->once();

        $output->shouldReceive('albums')
            ->with(
                [$album],
                $include,
                $user
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
                    'include' => $include,
                    'exact' => true,
                    'api_format' => 'json',
                    'auth' => 'stringauth',
                ],
                $user
            )
        );
    }
}
