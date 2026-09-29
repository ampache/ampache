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

namespace Ampache\Module\Api\RefreshReordered;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Playlist\Folder\PlaylistFolderRowIdsInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class RefreshPlaylistFolderActionTest extends MockeryTestCase
{
    private BrowseFactoryInterface&MockInterface $browseFactory;
    private PlaylistFolderRepositoryInterface&MockInterface $playlistFolderRepository;
    private RequestParserInterface&MockInterface $requestParser;
    private PlaylistFolderRowIdsInterface&MockInterface $rowIds;
    private RefreshPlaylistFolderAction $subject;

    public function testRunDoesNothingWhenFolderBelongsToAnotherUser(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $user       = $this->ownerUser(9);
        $gatekeeper->shouldReceive('getUser')->andReturn($user);

        $this->requestParser->shouldReceive('getFromRequest')->with('id')->andReturn('5');
        $this->playlistFolderRepository->shouldReceive('findById')->with(5)->andReturn($this->folder(5, 1, 0, 'Not Mine'));

        $this->browseFactory->shouldReceive('create')->never();

        self::assertNull($this->subject->run($this->mock(ServerRequestInterface::class), $gatekeeper));
    }

    public function testRunDoesNothingWhenNotLoggedIn(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $gatekeeper->shouldReceive('getUser')->andReturn(null);

        $this->browseFactory->shouldReceive('create')->never();

        self::assertNull($this->subject->run($this->mock(ServerRequestInterface::class), $gatekeeper));
    }

    public function testRunRendersOneFolderLevelAndReturnsNull(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $user       = $this->ownerUser(9);
        $gatekeeper->shouldReceive('getUser')->andReturn($user);

        $folder = $this->folder(5, 9, 0, 'Rock');
        $browse = $this->mock(Browse::class);

        $this->requestParser->shouldReceive('getFromRequest')->with('id')->andReturn('5');
        $this->playlistFolderRepository->shouldReceive('findById')->with(5)->andReturn($folder);
        $this->rowIds->shouldReceive('getRowIds')->with($user, $folder)->once()->andReturn(['search-20']);

        $this->browseFactory->shouldReceive('create')->withNoArgs()->once()->andReturn($browse);
        $browse->shouldReceive('set_type')->with('playlist_folder')->once();
        $browse->shouldReceive('set_show_header')->with(false)->once();
        $browse->shouldReceive('set_static_content')->with(true)->once();
        $browse->shouldReceive('add_supplemental_object')->with('playlist_folder', $folder)->once();
        $browse->shouldReceive('show_objects')->with(['search-20'], true)->once();
        $browse->shouldReceive('store')->withNoArgs()->once();

        self::assertNull($this->subject->run($this->mock(ServerRequestInterface::class), $gatekeeper));
    }

    public function testRunRendersTheRootLevelAndReturnsNull(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $user       = $this->ownerUser(9);
        $gatekeeper->shouldReceive('getUser')->andReturn($user);

        $browse = $this->mock(Browse::class);

        $this->requestParser->shouldReceive('getFromRequest')->with('id')->andReturn('0');
        $this->playlistFolderRepository->shouldReceive('findById')->never();
        $this->rowIds->shouldReceive('getRowIds')->with($user, null)->once()->andReturn(['playlist_folder-3', 'playlist-10']);

        $this->browseFactory->shouldReceive('create')->withNoArgs()->once()->andReturn($browse);
        $browse->shouldReceive('set_type')->with('playlist_folder')->once();
        $browse->shouldReceive('set_show_header')->with(false)->once();
        $browse->shouldReceive('set_static_content')->with(true)->once();
        $browse->shouldReceive('add_supplemental_object')->never();
        $browse->shouldReceive('show_objects')->with(['playlist_folder-3', 'playlist-10'], true)->once();
        $browse->shouldReceive('store')->withNoArgs()->once();

        self::assertNull($this->subject->run($this->mock(ServerRequestInterface::class), $gatekeeper));
    }

    protected function setUp(): void
    {
        $this->requestParser            = $this->mock(RequestParserInterface::class);
        $this->browseFactory            = $this->mock(BrowseFactoryInterface::class);
        $this->playlistFolderRepository = $this->mock(PlaylistFolderRepositoryInterface::class);
        $this->rowIds                   = $this->mock(PlaylistFolderRowIdsInterface::class);

        $this->subject = new RefreshPlaylistFolderAction(
            $this->requestParser,
            $this->browseFactory,
            $this->playlistFolderRepository,
            $this->rowIds,
        );
    }

    private function folder(int $id, int $userId, int $parentId, string $name): PlaylistFolder
    {
        return PlaylistFolder::fromRow([
            'id' => $id,
            'user' => $userId,
            'parent' => $parentId,
            'name' => $name,
        ]);
    }

    private function ownerUser(int $userId): User&MockInterface
    {
        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn($userId);

        return $user;
    }
}
