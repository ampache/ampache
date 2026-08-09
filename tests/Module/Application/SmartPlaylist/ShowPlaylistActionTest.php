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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ShowPlaylistActionTest extends MockeryTestCase
{
    private BrowseFactoryInterface&MockInterface $browseFactory;
    private FunctionCheckerInterface&MockInterface $functionChecker;
    private LoggerInterface&MockObject $logger;
    private ModelFactoryInterface&MockInterface $modelFactory;
    private ?ShowAction $subject;
    private UiInterface&MockInterface $ui;
    private ZipHandlerInterface&MockInterface $zipHandler;

    /**
     * The smartlist page itself is not asserted here: rendering it reaches `Ajax` and `Access`, both of
     * which resolve out of the DI container, and the unit bootstrap has none. The markup is verified over
     * http against the docker instance instead.
     */
    public function testRunSkipsRenderingAPlaylistThatDoesNotExist(): void
    {
        $search     = $this->mock(Smartlist::class);
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $playlistId = 666;

        $this->modelFactory->shouldReceive('createSmartlist')
            ->with($playlistId)
            ->once()
            ->andReturn($search);

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['playlist_id' => (string) $playlistId]);

        $search->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturn(true);

        $this->ui->shouldReceive('showHeader')->withNoArgs()->once();
        $this->ui->shouldReceive('showQueryStats')->withNoArgs()->once();
        $this->ui->shouldReceive('showFooter')->withNoArgs()->once();

        $this->logger->expects(static::once())->method('warning');

        ob_start();

        try {
            $result = $this->subject->run($request, $gatekeeper);
        } finally {
            $output = (string) ob_get_clean();
        }

        $this->assertNull($result);
        $this->assertStringNotContainsString('smartplaylist_row_', $output);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->ui              = $this->mock(UiInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
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
