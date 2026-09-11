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
use Ampache\Module\Authorization\Check\FunctionCheckerInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Module\Util\UiInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Mockery\MockInterface;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowActionTest extends MockeryTestCase
{
    private BrowseFactoryInterface&MockInterface $browseFactory;
    private FunctionCheckerInterface&MockInterface $functionChecker;
    private LoggerInterface&MockInterface $logger;
    private ModelFactoryInterface&MockInterface $modelFactory;
    private ShowAction $subject;
    private UiInterface&MockInterface $ui;
    private ZipHandlerInterface&MockInterface $zipHandler;

    public function testRunHidesAPrivateSearchFromAnotherUser(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $playlist   = $this->mock(Smartlist::class);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['playlist_id' => '5']);

        $this->modelFactory->shouldReceive('createSmartlist')
            ->with(5)
            ->once()
            ->andReturn($playlist);

        $playlist->shouldReceive('isNew')
            ->andReturnFalse();
        // not public, and the viewer does not collaborate: the search is not theirs to see
        $playlist->type = 'private';
        $playlist->shouldReceive('has_collaborate')
            ->andReturnFalse();
        // the tell that the guard fired: the items are never fetched or rendered
        $playlist->shouldReceive('get_items')
            ->never();

        $this->ui->shouldReceive('showHeader')->once();
        $this->ui->shouldReceive('showQueryStats')->once();
        $this->ui->shouldReceive('showFooter')->once();
        $this->logger->shouldReceive('warning')->once();

        ob_start();
        $result = $this->subject->run($request, $gatekeeper);
        ob_end_clean();

        self::assertNull($result);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->ui              = $this->mock(UiInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->zipHandler      = $this->mock(ZipHandlerInterface::class);
        $this->browseFactory   = $this->mock(BrowseFactoryInterface::class);
        $this->functionChecker = $this->mock(FunctionCheckerInterface::class);

        $this->subject = new ShowAction(
            $this->ui,
            $this->logger,
            $this->modelFactory,
            $this->zipHandler,
            $this->browseFactory,
            $this->functionChecker
        );
    }
}
