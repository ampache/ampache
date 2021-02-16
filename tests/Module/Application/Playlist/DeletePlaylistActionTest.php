<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Application\Playlist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

class DeletePlaylistActionTest extends MockeryTestCase
{
    /** @var ResponseFactoryInterface|MockInterface|null */
    private ?MockInterface $responseFactory;

    /** @var ConfigContainerInterface|MockInterface|null */
    private ?MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface|null */
    private ?MockInterface $modelFactory;

    private ?DeletePlaylistAction $subject;

    public function setUp(): void
    {
        $this->responseFactory = $this->mock(ResponseFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new DeletePlaylistAction(
            $this->modelFactory,
            $this->responseFactory,
            $this->configContainer
        );
    }

    public function testRunThrowsExceptionIfIdIsMissing(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunThrowsExceptionIfNotAccessible(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $playlist   = $this->mock(Playlist::class);

        $playlistId = 666;

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['playlist_id' => (string) $playlistId]);

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($playlistId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('has_access')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunDeletesAndReturnsResponse(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $playlist   = $this->mock(Playlist::class);
        $respone    = $this->mock(ResponseInterface::class);

        $playlistId = 666;
        $webPath    = 'some-path';

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['playlist_id' => (string) $playlistId]);

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with($playlistId)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('has_access')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();
        $playlist->shouldReceive('delete')
            ->withNoArgs()
            ->once();

        $this->responseFactory->shouldReceive('createResponse')
            ->with(StatusCode::FOUND)
            ->once()
            ->andReturn($respone);

        $respone->shouldReceive('withHeader')
            ->with(
                'Location',
                sprintf(
                    '%s/browse.php?action=playlist',
                    $webPath
                )
            )
            ->once()
            ->andReturnSelf();

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            $respone,
            $this->subject->run($request, $gatekeeper)
        );
    }
}
