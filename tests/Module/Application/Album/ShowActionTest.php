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

namespace Ampache\Module\Application\Album;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Album\Edit\AlbumEditabilityCheckerInterface;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowActionTest extends MockeryTestCase
{
    private BrowseFactoryInterface&MockInterface $browseFactory;
    private ConfigContainerInterface&MockInterface $configContainer;
    private AlbumEditabilityCheckerInterface&MockInterface $editabilityChecker;
    private FunctionCheckerInterface&MockInterface $functionChecker;
    private LoggerInterface&MockInterface $logger;
    private ModelFactoryInterface&MockInterface $modelFactory;
    private ShowAction $subject;
    private UiInterface&MockInterface $ui;
    private ZipHandlerInterface&MockInterface $zipHandler;

    /**
     * Both album pages are views now, and rendering either reaches `Art` and `Ajax`, which resolve from the
     * DI container the unit bootstrap has none of. What the action decides is covered elsewhere: the
     * editability rule by `AlbumEditabilityCheckerTest`, and the rendered markup over http.
     */
    public function testRunShowsErrorIfAlbumDoesNotExist(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);
        $user       = $this->mock(User::class);

        $albumId        = 42;
        $album->catalog = 1;

        $user->catalogs['music'] = [1];

        $gatekeeper->shouldReceive('getUser')
            ->withNoArgs()
            ->once()
            ->andReturn($user);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['album' => (string) $albumId]);

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);

        $album->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->logger->shouldReceive('warning')
            ->with(
                'Requested an album that does not exist',
                [LegacyLogger::CONTEXT_TYPE => ShowAction::class]
            )
            ->once();

        $this->expectOutputString('You have requested an object that does not exist');

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->modelFactory       = $this->mock(ModelFactoryInterface::class);
        $this->browseFactory      = $this->mock(BrowseFactoryInterface::class);
        $this->ui                 = $this->mock(UiInterface::class);
        $this->logger             = $this->mock(LoggerInterface::class);
        $this->editabilityChecker = $this->mock(AlbumEditabilityCheckerInterface::class);
        $this->functionChecker    = $this->mock(FunctionCheckerInterface::class);
        $this->configContainer    = $this->mock(ConfigContainerInterface::class);
        $this->zipHandler         = $this->mock(ZipHandlerInterface::class);

        $this->subject = new ShowAction(
            $this->modelFactory,
            $this->ui,
            $this->logger,
            $this->configContainer,
            $this->zipHandler,
            $this->browseFactory,
            $this->editabilityChecker,
            $this->functionChecker
        );
    }
}
