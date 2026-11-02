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

namespace Ampache\Module\Application\Stream;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\RequestParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class PlaylistRandomActionTest extends MockeryTestCase
{
    private ConfigContainerInterface&MockInterface $configContainer;
    private LoggerInterface&MockInterface $logger;
    private ModelFactoryInterface&MockInterface $modelFactory;
    private RequestParserInterface&MockInterface $requestParser;
    private PlaylistRandomAction $subject;

    public function testRunRefusesAPrivatePlaylistTheViewerCannotSee(): void
    {
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $playlist   = $this->mock(Playlist::class);
        $user       = $this->mock(User::class);

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->andReturnFalse();
        $this->configContainer->shouldReceive('isAuthenticationEnabled')
            ->withNoArgs()
            ->andReturnFalse();

        $this->requestParser->shouldReceive('getFromRequest')
            ->with('playlist_id')
            ->andReturn('42');

        $this->modelFactory->shouldReceive('createPlaylist')
            ->with(42)
            ->andReturn($playlist);

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->andReturn($user);

        $playlist->shouldReceive('isVisible')
            ->with($user)
            ->andReturn(false);

        // the tell that the guard fired: the members are never even read, let alone streamed
        $playlist->shouldNotReceive('get_random_items');

        $this->expectException(AccessDeniedException::class);

        $this->subject->run(
            $this->mock(ServerRequestInterface::class),
            $gatekeeper
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->requestParser   = $this->mock(RequestParserInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new PlaylistRandomAction(
            $this->requestParser,
            $this->modelFactory,
            $this->logger,
            $this->configContainer,
        );
    }
}
