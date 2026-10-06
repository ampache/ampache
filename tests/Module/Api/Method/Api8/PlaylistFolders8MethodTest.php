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
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class PlaylistFolders8MethodTest extends MockeryTestCase
{
    private PlaylistFolderRepositoryInterface|MockInterface|null $playlistFolderRepository;
    private ?PlaylistFolders8Method $subject;

    public function testHandleAppendsOtherUsersSharedRootFoldersAfterTheCallersOwnTree(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $own    = PlaylistFolder::fromRow(['id' => 3, 'user' => 9, 'parent' => 0, 'name' => 'Live']);
        $shared = PlaylistFolder::fromRow(['id' => 11, 'user' => 20, 'parent' => 0, 'name' => 'Metal', 'type' => 'public']);

        $user->shouldReceive('getId')->andReturn(9);
        $this->playlistFolderRepository->shouldReceive('getTree')->with($user)->once()->andReturn([$own]);
        $this->playlistFolderRepository->shouldReceive('getPublicRootFolders')->with(9)->once()->andReturn([$shared]);

        $output->shouldReceive('setOffset')->once();
        $output->shouldReceive('setLimit')->once();
        $output->shouldReceive('playlistFolders')
            ->with(8, [$own, $shared], $user)
            ->once()
            ->andReturn('ok-result');
        $response->shouldReceive('getBody')->andReturn($stream);
        $stream->shouldReceive('write')->with('ok-result')->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    public function testHandleWritesEmptyWhenNeitherOwnNorSharedFoldersExist(): void
    {
        $gatekeeper = $this->mock(GatekeeperInterface::class);
        $response   = $this->mock(ResponseInterface::class);
        $output     = $this->mock(ApiOutputInterface::class);
        $user       = $this->mock(User::class);
        $stream     = $this->mock(StreamInterface::class);

        $user->shouldReceive('getId')->andReturn(9);
        $this->playlistFolderRepository->shouldReceive('getTree')->with($user)->once()->andReturn([]);
        $this->playlistFolderRepository->shouldReceive('getPublicRootFolders')->with(9)->once()->andReturn([]);

        $output->shouldReceive('writeEmpty')->with(8, 'playlist_folder')->once()->andReturn('empty-result');
        $response->shouldReceive('getBody')->andReturn($stream);
        $stream->shouldReceive('write')->with('empty-result')->once();

        $this->assertSame(
            $response,
            $this->subject->handle(
                $gatekeeper,
                $response,
                $output,
                ['api_format' => 'json', 'auth' => 'some-auth'],
                $user,
                8
            )
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->playlistFolderRepository = $this->mock(PlaylistFolderRepositoryInterface::class);

        $this->subject = new PlaylistFolders8Method(
            $this->playlistFolderRepository
        );
    }
}
