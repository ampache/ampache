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

namespace Ampache\Module\Application\PlaylistFolder;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class SetSortOrderActionTest extends MockeryTestCase
{
    private GuiGatekeeperInterface&MockInterface $gatekeeper;
    private PlaylistFolderRepositoryInterface&MockInterface $playlistFolderRepository;
    private RequestParserInterface&MockInterface $requestParser;
    private SetSortOrderAction $subject;
    private UiInterface&MockInterface $ui;

    public function testRunAllowsReorderingAtTheRoot(): void
    {
        $user = $this->ownerUser(9);

        $this->gatekeeper->shouldReceive('mayAccess')->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($user);
        $this->requestParser->shouldReceive('getFromRequest')->with('folder')->andReturn('0');
        $this->requestParser->shouldReceive('getFromRequest')->with('order')->andReturn('playlist-10');
        $this->requestParser->shouldReceive('getFromRequest')->with('offset')->andReturn('0');

        $this->playlistFolderRepository->shouldReceive('findById')->never();
        $this->playlistFolderRepository->shouldReceive('place')->with($user, 10, 'playlist', 0, 1)->once()->andReturn(true);

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        self::assertNull($this->subject->run($this->request(), $this->gatekeeper));
    }

    public function testRunPlacesAndUpdatesEachTokenInDraggedOrder(): void
    {
        $user      = $this->ownerUser(9);
        $subfolder = $this->folder(3, 9, 5, 'Live');

        $this->gatekeeper->shouldReceive('mayAccess')->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($user);
        $this->requestParser->shouldReceive('getFromRequest')->with('folder')->andReturn('5');
        $this->playlistFolderRepository->shouldReceive('findById')->with(5)->andReturn($this->folder(5, 9, 0, 'Rock'));
        $this->requestParser->shouldReceive('getFromRequest')->with('order')->andReturn('playlist_folder-3;playlist-10;search-20');
        $this->requestParser->shouldReceive('getFromRequest')->with('offset')->andReturn('0');
        $this->playlistFolderRepository->shouldReceive('findById')->with(3)->andReturn($subfolder);

        $this->playlistFolderRepository->shouldReceive('update')->with(3, null, null, 1)->once()->andReturn(true);
        $this->playlistFolderRepository->shouldReceive('place')->with($user, 10, 'playlist', 5, 2)->once()->andReturn(true);
        $this->playlistFolderRepository->shouldReceive('place')->with($user, 20, 'search', 5, 3)->once()->andReturn(true);

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        self::assertNull($this->subject->run($this->request(), $this->gatekeeper));
    }

    public function testRunSkipsASubfolderTokenBelongingToAnotherUser(): void
    {
        $user          = $this->ownerUser(9);
        $othersFolder  = $this->folder(3, 1, 0, 'Not Mine');

        $this->gatekeeper->shouldReceive('mayAccess')->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($user);
        $this->requestParser->shouldReceive('getFromRequest')->with('folder')->andReturn('5');
        $this->playlistFolderRepository->shouldReceive('findById')->with(5)->andReturn($this->folder(5, 9, 0, 'Rock'));
        $this->requestParser->shouldReceive('getFromRequest')->with('order')->andReturn('playlist_folder-3');
        $this->requestParser->shouldReceive('getFromRequest')->with('offset')->andReturn('0');
        $this->playlistFolderRepository->shouldReceive('findById')->with(3)->andReturn($othersFolder);

        $this->playlistFolderRepository->shouldReceive('update')->never();

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        self::assertNull($this->subject->run($this->request(), $this->gatekeeper));
    }

    public function testRunThrowsWhenFolderBelongsToAnotherUser(): void
    {
        self::expectException(AccessDeniedException::class);

        $folder = $this->folder(5, 1, 0, 'Rock');
        $viewer = $this->ownerUser(2);

        $this->gatekeeper->shouldReceive('mayAccess')->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($viewer);
        $this->requestParser->shouldReceive('getFromRequest')->with('folder')->andReturn('5');
        $this->playlistFolderRepository->shouldReceive('findById')->with(5)->andReturn($folder);

        $this->subject->run($this->request(), $this->gatekeeper);
    }

    public function testRunThrowsWhenNotLoggedIn(): void
    {
        self::expectException(AccessDeniedException::class);

        $this->gatekeeper->shouldReceive('mayAccess')->andReturn(false);

        $this->subject->run($this->request(), $this->gatekeeper);
    }

    protected function setUp(): void
    {
        $this->gatekeeper               = $this->mock(GuiGatekeeperInterface::class);
        $this->playlistFolderRepository = $this->mock(PlaylistFolderRepositoryInterface::class);
        $this->requestParser            = $this->mock(RequestParserInterface::class);
        $this->ui                       = $this->mock(UiInterface::class);

        $this->subject = new SetSortOrderAction(
            $this->requestParser,
            $this->playlistFolderRepository,
            $this->ui,
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

    private function request(): ServerRequestInterface&MockInterface
    {
        return $this->mock(ServerRequestInterface::class);
    }
}
