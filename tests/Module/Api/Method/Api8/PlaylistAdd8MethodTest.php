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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PlaylistAdd8MethodTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?PlaylistAdd8Method $subject;

    public function testHandleExpandsAListTheCallerMaySee(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $target     = $this->mock(Playlist::class);
        $source     = $this->mock(Playlist::class);
        $stream     = $this->mock(StreamInterface::class);

        $this->modelFactory->shouldReceive('createPlaylist')->with(5)->once()->andReturn($target);
        $target->shouldReceive('has_collaborate')->with($user)->andReturn(true);

        $this->modelFactory->shouldReceive('createPlaylist')->with(9)->once()->andReturn($source);
        $source->shouldReceive('isNew')->andReturn(false);
        $source->type = 'private';
        $source->shouldReceive('has_collaborate')->with($user)->andReturn(true);
        $source->shouldReceive('get_songs')->andReturn([101, 102]);

        $target->shouldReceive('add_songs')->with([101, 102])->once()->andReturn(true);

        $output->shouldReceive('success')->andReturn('ok-result');
        $response->shouldReceive('getBody')->andReturn($stream);
        $stream->shouldReceive('write')->with('ok-result')->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['filter' => '5', 'id' => '9', 'object_type' => 'playlist', 'api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    /**
     * Expanding another user's private list into your own playlist must be refused: the source list you
     * neither own nor collaborate on reads as not-found, and nothing is copied into the target.
     */
    public function testHandleRefusesExpandingAPrivateSourceList(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $target     = $this->mock(Playlist::class);
        $source     = $this->mock(Playlist::class);

        $this->modelFactory->shouldReceive('createPlaylist')->with(5)->once()->andReturn($target);
        $target->shouldReceive('has_collaborate')->with($user)->andReturn(true);
        // the target must never receive the foreign list's songs
        $target->shouldNotReceive('add_songs');

        $this->modelFactory->shouldReceive('createPlaylist')->with(9)->once()->andReturn($source);
        $source->shouldReceive('isNew')->andReturn(false);
        $source->type = 'private';
        $source->shouldReceive('has_collaborate')->with($user)->andReturn(false);

        $this->expectException(ResultEmptyException::class);
        $this->expectExceptionMessage('9');

        $this->subject->handle(
            $gatekeeper,
            $response,
            $output,
            ['filter' => '5', 'id' => '9', 'object_type' => 'playlist', 'api_format' => 'json', 'auth' => 'some-auth'],
            $user,
            8
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);

        $this->subject = new PlaylistAdd8Method(
            $this->modelFactory
        );
    }
}
