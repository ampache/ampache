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

namespace Ampache\Module\Application\SmartPlaylist;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;

class UpdatePlaylistActionTest extends MockeryTestCase
{
    private ModelFactoryInterface|MockInterface $modelFactory;
    private UpdatePlaylistAction $subject;
    private UiInterface|MockInterface $ui;
    private ZipHandlerInterface|MockInterface $zipHandler;

    public function testRunThrowsExceptionIfAccessDenied(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $search     = $this->mock(Smartlist::class);

        $playlistId = 666;

        $this->expectException(AccessDeniedException::class);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['playlist_id' => (string) $playlistId]);

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->modelFactory->shouldReceive('createSmartlist')
            ->with($playlistId)
            ->once()
            ->andReturn($search);

        $search->shouldReceive('has_access')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->subject->run(
            $request,
            $gatekeeper
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->ui           = $this->mock(UiInterface::class);
        $this->modelFactory = $this->mock(ModelFactoryInterface::class);
        $this->zipHandler   = $this->mock(ZipHandlerInterface::class);

        $this->subject = new UpdatePlaylistAction(
            $this->ui,
            $this->modelFactory,
            $this->zipHandler
        );
    }
}
