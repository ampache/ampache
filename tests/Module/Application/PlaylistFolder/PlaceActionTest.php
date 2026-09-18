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
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use Ampache\Repository\PlaylistFolderRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PlaceActionTest extends MockeryTestCase
{
    private ConfigContainerInterface&MockInterface $configContainer;
    private GuiGatekeeperInterface&MockInterface $gatekeeper;
    private PlaylistFolderRepositoryInterface&MockInterface $playlistFolderRepository;
    private ResponseFactoryInterface&MockInterface $responseFactory;
    private PlaceAction $subject;

    public function testRunPlacesAnObjectIntoAFolder(): void
    {
        $user = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(9);
        $folder = PlaylistFolder::fromRow(['id' => 3, 'user' => 9, 'parent' => 0, 'name' => 'Rock']);

        $this->gatekeeper->shouldReceive('mayAccess')->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($user);
        $this->playlistFolderRepository->shouldReceive('findById')->with(3)->andReturn($folder);
        $this->playlistFolderRepository->shouldReceive('place')->with($user, 42, 'playlist', 3)->once();
        $this->playlistFolderRepository->shouldReceive('unplace')->never();

        $response = $this->expectRedirectTo('https://ampache.test/browse.php?action=playlist_folder');

        self::assertSame(
            $response,
            $this->subject->run($this->request(['object_type' => 'playlist', 'object_id' => '42', 'folder' => '3']), $this->gatekeeper)
        );
    }

    public function testRunThrowsForAnUnplaceableObjectType(): void
    {
        self::expectException(AccessDeniedException::class);

        $this->gatekeeper->shouldReceive('mayAccess')->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($this->mock(User::class));

        $this->subject->run($this->request(['object_type' => 'collection', 'object_id' => '42', 'folder' => '0']), $this->gatekeeper);
    }

    public function testRunThrowsWhenTargetFolderIsNotOwnedByTheUser(): void
    {
        self::expectException(AccessDeniedException::class);

        $user   = $this->mock(User::class);
        $user->shouldReceive('getId')->andReturn(9);
        $folder = PlaylistFolder::fromRow(['id' => 3, 'user' => 1, 'parent' => 0, 'name' => 'Rock']);

        $this->gatekeeper->shouldReceive('mayAccess')->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($user);
        $this->playlistFolderRepository->shouldReceive('findById')->with(3)->andReturn($folder);

        $this->subject->run($this->request(['object_type' => 'playlist', 'object_id' => '42', 'folder' => '3']), $this->gatekeeper);
    }

    public function testRunUnplacesWhenTargetFolderIsRoot(): void
    {
        $user = $this->mock(User::class);

        $this->gatekeeper->shouldReceive('mayAccess')->andReturn(true);
        $this->gatekeeper->shouldReceive('getUser')->andReturn($user);
        $this->playlistFolderRepository->shouldReceive('findById')->never();
        $this->playlistFolderRepository->shouldReceive('unplace')->with($user, 42, 'search')->once();

        $response = $this->expectRedirectTo('https://ampache.test/browse.php?action=playlist_folder&folder=7');

        self::assertSame(
            $response,
            $this->subject->run($this->request(['object_type' => 'search', 'object_id' => '42', 'folder' => '0', 'from' => '7']), $this->gatekeeper)
        );
    }

    protected function setUp(): void
    {
        $this->configContainer           = $this->mock(ConfigContainerInterface::class);
        $this->gatekeeper                = $this->mock(GuiGatekeeperInterface::class);
        $this->playlistFolderRepository  = $this->mock(PlaylistFolderRepositoryInterface::class);
        $this->responseFactory           = $this->mock(ResponseFactoryInterface::class);

        $this->configContainer->shouldReceive('getWebPath')->andReturn('https://ampache.test');

        $this->subject = new PlaceAction(
            $this->configContainer,
            $this->playlistFolderRepository,
            $this->responseFactory,
        );
    }

    private function expectRedirectTo(string $location): ResponseInterface&MockInterface
    {
        $response = $this->mock(ResponseInterface::class);
        $response->shouldReceive('withHeader')->with('Location', $location)->andReturn($response);
        $this->responseFactory->shouldReceive('createResponse')->andReturn($response);

        return $response;
    }

    /**
     * @param array<string, string> $params
     */
    private function request(array $params): ServerRequestInterface&MockInterface
    {
        $request = $this->mock(ServerRequestInterface::class);
        $request->shouldReceive('getQueryParams')->andReturn($params);

        return $request;
    }
}
