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

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playlist\Folder\PlaylistFolderTreeFormatterInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;

class EditActionTest extends MockeryTestCase
{
    private ConfigContainerInterface&MockInterface $configContainer;
    private GuiGatekeeperInterface&MockInterface $gatekeeper;
    private PlaylistFolderRepositoryInterface&MockInterface $playlistFolderRepository;
    private RequestParserInterface&MockInterface $requestParser;
    private EditAction $subject;
    private PlaylistFolderTreeFormatterInterface&MockInterface $treeFormatter;
    private UiInterface&MockInterface $ui;

    public function testRunAddsErrorWhenNameIsTakenBySibling(): void
    {
        $user   = $this->ownerUser(9);
        $folder = $this->folder(5, 9, 0, 'Rock');

        $this->guardsPass($user, $folder);
        $this->playlistFolderRepository->shouldReceive('update')->once()->andReturn(false);
        $this->treeFormatter->shouldReceive('flatten')->with($user, 5)->andReturn([]);

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        ob_start();
        self::assertNull($this->subject->run($this->request(), $this->gatekeeper));
        ob_end_clean();

        self::assertTrue(AmpError::occurred());
        self::assertSame('That name already exists', AmpError::get('name'));
    }

    public function testRunRejectsMoveThatWouldCreateACycle(): void
    {
        $user   = $this->ownerUser(9);
        $folder = $this->folder(5, 9, 0, 'Rock');

        $this->guardsPass($user, $folder, parentId: '3');
        $this->playlistFolderRepository->shouldReceive('wouldCycle')->with(5, 3)->once()->andReturn(true);
        $this->playlistFolderRepository->shouldReceive('update')->never();
        $this->treeFormatter->shouldReceive('flatten')->with($user, 5)->andReturn([]);

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        ob_start();
        self::assertNull($this->subject->run($this->request(), $this->gatekeeper));
        ob_end_clean();

        self::assertTrue(AmpError::occurred());
        self::assertSame('A folder cannot contain itself', AmpError::get('parent'));
    }

    public function testRunThrowsWhenFolderBelongsToAnotherUser(): void
    {
        self::expectException(AccessDeniedException::class);

        $folder = $this->folder(5, 1, 0, 'Rock');

        $viewer = $this->mock(User::class);
        $viewer->shouldReceive('getId')->andReturn(2);

        $this->gatekeeper->shouldReceive('mayAccess')->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($viewer);
        $this->requestParser->shouldReceive('verifyForm')->with('edit_playlist_folder')->andReturn(true);
        $this->requestParser->shouldReceive('getFromRequest')->with('folder')->andReturn('5');
        $this->playlistFolderRepository->shouldReceive('findById')->with(5)->andReturn($folder);

        $this->subject->run($this->request(), $this->gatekeeper);
    }

    public function testRunUpdatesFolderOnValidInput(): void
    {
        $user   = $this->ownerUser(9);
        $folder = $this->folder(5, 9, 0, 'Rock');

        $this->guardsPass($user, $folder);
        $this->playlistFolderRepository->shouldReceive('wouldCycle')->never();
        $this->playlistFolderRepository->shouldReceive('update')->with(5, 'Jazz', 0)->once()->andReturn(true);

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showConfirmation')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();

        ob_start();
        self::assertNull($this->subject->run($this->request(), $this->gatekeeper));
        ob_end_clean();

        self::assertFalse(AmpError::occurred());
    }

    protected function setUp(): void
    {
        $this->configContainer           = $this->mock(ConfigContainerInterface::class);
        $this->gatekeeper                = $this->mock(GuiGatekeeperInterface::class);
        $this->playlistFolderRepository  = $this->mock(PlaylistFolderRepositoryInterface::class);
        $this->requestParser             = $this->mock(RequestParserInterface::class);
        $this->treeFormatter             = $this->mock(PlaylistFolderTreeFormatterInterface::class);
        $this->ui                        = $this->mock(UiInterface::class);

        $this->configContainer->shouldReceive('getWebPath')->andReturn('https://ampache.test');

        $this->subject = new EditAction(
            $this->configContainer,
            $this->playlistFolderRepository,
            $this->treeFormatter,
            $this->requestParser,
            $this->ui,
        );

        // AmpError's state is a private static with no public reset, so reset it here or a failure in one test leaks into the next
        new ReflectionProperty(AmpError::class, 'errors')->setValue(null, []);
        new ReflectionProperty(AmpError::class, 'state')->setValue(null, false);
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

    private function guardsPass(User&MockInterface $user, PlaylistFolder $folder, string $parentId = '0'): void
    {
        $this->gatekeeper->shouldReceive('mayAccess')->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($user);
        $this->requestParser->shouldReceive('verifyForm')->with('edit_playlist_folder')->andReturn(true);
        $this->requestParser->shouldReceive('getFromRequest')->with('folder')->andReturn((string) $folder->getId());
        $this->playlistFolderRepository->shouldReceive('findById')->with($folder->getId())->andReturn($folder);
        $this->requestParser->shouldReceive('getFromRequest')->with('name')->andReturn('Jazz');
        $this->requestParser->shouldReceive('getFromRequest')->with('parent')->andReturn($parentId);
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
