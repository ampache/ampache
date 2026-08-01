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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\MockeryTestCase;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AlbumDiskSongs8MethodTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?AlbumDiskSongs8Method $subject;

    public function testHandleReturnsEmptyResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);
        $parent     = $this->mock(AlbumDisk::class);

        $objectId = 666;
        $result   = 'empty-result';

        $this->modelFactory->shouldReceive('createAlbumDisk')
            ->with($objectId)
            ->once()
            ->andReturn($parent);

        $parent->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('song')
            ->once();
        $browse->shouldReceive('set_skip_catalog_check')
            ->with(true)
            ->once();
        $browse->shouldReceive('set_sort_order')
            ->with('', ['track', 'ASC'])
            ->once();
        $browse->shouldReceive('set_filter')
            ->with('album_disk', $objectId)
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $output->shouldReceive('writeEmpty')
            ->with(8, 'song')
            ->once()
            ->andReturn($result);

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')
            ->with($result)
            ->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => (string) $objectId, 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    public function testHandleReturnsResult(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $browse     = $this->mock(Browse::class);
        $stream     = $this->mock(StreamInterface::class);
        $parent     = $this->mock(AlbumDisk::class);

        $objectId = 666;
        $results  = [1, 2, 3];
        $result   = 'some-result';

        $this->modelFactory->shouldReceive('createAlbumDisk')
            ->with($objectId)
            ->once()
            ->andReturn($parent);

        $parent->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->modelFactory->shouldReceive('createBrowse')
            ->with(null, false)
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('set_user_id')
            ->with($user)
            ->once();
        $browse->shouldReceive('set_type')
            ->with('song')
            ->once();
        $browse->shouldReceive('set_skip_catalog_check')
            ->with(true)
            ->once();
        $browse->shouldReceive('set_sort_order')
            ->with('', ['track', 'ASC'])
            ->once();
        $browse->shouldReceive('set_filter')
            ->with('album_disk', $objectId)
            ->once();
        $browse->shouldReceive('set_conditions')
            ->with('')
            ->once();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn($results);

        $output->shouldReceive('setOffset')
            ->with(8, 0)
            ->once();
        $output->shouldReceive('setLimit')
            ->with(8, 0)
            ->once();
        $output->shouldReceive('songs')
            ->with(8, $results, $user, 'some-auth')
            ->once()
            ->andReturn($result);

        $response->shouldReceive('getBody')
            ->withNoArgs()
            ->once()
            ->andReturn($stream);
        $stream->shouldReceive('write')
            ->with($result)
            ->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => (string) $objectId, 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    public function testHandleThrowsExceptionIfAlbumDiskDoesNotExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $parent     = $this->mock(AlbumDisk::class);

        $objectId = 666;

        $this->modelFactory->shouldReceive('createAlbumDisk')
            ->with($objectId)
            ->once()
            ->andReturn($parent);

        $parent->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage((string) $objectId);

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => (string) $objectId, 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            8
        );
    }

    public function testHandleThrowsExceptionIfFilterIsMissing(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);

        $this->expectException(RequestParamMissingException::class);
        $this->expectExceptionMessage(sprintf(T_('Bad Request: %s'), 'filter'));

        /** @noinspection PhpMissingArrayKeyInspection */
        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            8
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new AlbumDiskSongs8Method(
            $this->modelFactory
        );
    }
}
