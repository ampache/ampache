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

namespace Ampache\Module\Application\Artist;

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowActionTest extends MockeryTestCase
{
    private AlbumRepositoryInterface|MockInterface|null $albumRepository;
    private BrowseFactoryInterface&MockInterface $browseFactory;
    private ConfigContainerInterface|MockInterface|null $configContainer;
    private LoggerInterface|MockInterface|null $logger;
    private ModelFactoryInterface|MockInterface|null $modelFactory;
    private ?ShowAction $subject;
    private UiInterface|MockInterface|null $ui;
    private ZipHandlerInterface|MockInterface|null $zipHandler;

    /**
     * The artist page is a view now, and rendering it reaches `Art` and `Ajax`, which resolve from the DI
     * container the unit bootstrap has none of. Its markup is verified over http instead; what remains
     * here is the decision the action makes before rendering.
     */
    public function testRunsShowsErrorIfArtistIsNew(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $artist     = $this->mock(Artist::class);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->modelFactory->shouldReceive('createArtist')
            ->with(0)
            ->once()
            ->andReturn($artist);

        $artist->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnTrue();

        $this->logger->shouldReceive('warning')
            ->with(
                'Requested an artist that does not exist',
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
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->browseFactory   = $this->mock(BrowseFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->ui              = $this->mock(UiInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);
        $this->albumRepository = $this->mock(AlbumRepositoryInterface::class);
        $this->zipHandler      = $this->mock(ZipHandlerInterface::class);

        $this->subject = new ShowAction(
            $this->modelFactory,
            $this->configContainer,
            $this->ui,
            $this->logger,
            $this->albumRepository,
            $this->zipHandler,
            $this->browseFactory,
            $this->mock(FunctionCheckerInterface::class)
        );
    }
}
