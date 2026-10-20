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
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UnhideActionTest extends MockeryTestCase
{
    private ConfigContainerInterface&MockInterface $configContainer;
    private GuiGatekeeperInterface&MockInterface $gatekeeper;
    private PlaylistFolderRepositoryInterface&MockInterface $playlistFolderRepository;
    private ServerRequestInterface&MockInterface $requestMock;
    private ResponseFactoryInterface&MockInterface $responseFactory;
    private UnhideAction $subject;

    public function testRunThrowsWhenNotLoggedIn(): void
    {
        self::expectException(AccessDeniedException::class);

        $this->requestParser(0);
        $this->gatekeeper->shouldReceive('getUser')->andReturn(null);
        $this->playlistFolderRepository->shouldReceive('unhide')->never();

        $this->subject->run($this->request(), $this->gatekeeper);
    }

    public function testRunUnhidesAFolder(): void
    {
        $user = $this->viewerUser(2);

        $this->gatekeeper->shouldReceive('getUser')->andReturn($user);
        $this->requestParser(11);
        $this->playlistFolderRepository->shouldReceive('unhide')->with(2, 11)->once();

        $response = $this->mock(ResponseInterface::class);
        $response->shouldReceive('withHeader')->with('Location', 'https://ampache.test/browse.php?action=playlist_folder')->andReturn($response);
        $this->responseFactory->shouldReceive('createResponse')->andReturn($response);

        self::assertSame($response, $this->subject->run($this->request(), $this->gatekeeper));
    }

    protected function setUp(): void
    {
        $this->configContainer           = $this->mock(ConfigContainerInterface::class);
        $this->gatekeeper                = $this->mock(GuiGatekeeperInterface::class);
        $this->playlistFolderRepository  = $this->mock(PlaylistFolderRepositoryInterface::class);
        $this->responseFactory           = $this->mock(ResponseFactoryInterface::class);

        $this->configContainer->shouldReceive('getWebPath')->andReturn('https://ampache.test');

        $this->subject = new UnhideAction(
            $this->configContainer,
            $this->playlistFolderRepository,
            $this->responseFactory,
        );
    }

    private function request(): ServerRequestInterface&MockInterface
    {
        return $this->requestMock;
    }

    private function requestParser(int $folderId): void
    {
        $this->requestMock = $this->mock(ServerRequestInterface::class);
        $this->requestMock->shouldReceive('getQueryParams')->andReturn(['folder' => (string) $folderId]);
    }

    private function viewerUser(int $userId): User&MockInterface
    {
        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn($userId);

        return $user;
    }
}
