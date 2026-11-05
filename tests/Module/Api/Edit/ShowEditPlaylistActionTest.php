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
 */

namespace Ampache\Module\Api\Edit;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\Playlist\NewPlaylistDialogAdapterInterface;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\ShareRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class ShowEditPlaylistActionTest extends TestCase
{
    private BrowseFactoryInterface&MockObject $browseFactory;
    private ConfigContainerInterface&MockObject $configContainer;
    private GuiGatekeeperInterface&MockObject $gatekeeper;
    private GuiFactoryInterface&MockObject $guiFactory;
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private LoggerInterface&MockObject $logger;
    private ResponseFactoryInterface&MockObject $responseFactory;
    private ShareRepositoryInterface&MockObject $shareRepository;
    private StreamFactoryInterface&MockObject $streamFactory;
    private ShowEditPlaylistAction $subject;

    /**
     * `id`/`groups` round-trip into the dialog's inline onclick JS string, html-escaped but never JS-escaped,
     * so a quote in either value breaks out of it (the entity decodes back to a quote before JS compiles it).
     */
    public function testHandleStripsQuotesFromIdAndGroups(): void
    {
        $libitem = $this->createMock(library_item::class);
        $libitem->method('get_user_owner')->willReturn(null);

        $this->libraryItemLoader->method('load')->willReturn($libitem);

        $this->gatekeeper->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, self::anything())
            ->willReturn(true);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getParsedBody')->willReturn([]);
        $request->method('getQueryParams')->willReturn([
            'action' => 'show_edit_playlist',
            'object_type' => 'song',
            'id' => "5,12,88'); alert(1); ('",
            'groups' => 'song,album"); alert(2); ("',
            'browse_id' => '0',
        ]);

        $dialog = $this->createMock(NewPlaylistDialogAdapterInterface::class);
        $dialog->method('render')->willReturn('');

        $this->guiFactory->expects(self::once())
            ->method('createNewPlaylistDialogAdapter')
            ->with($this->gatekeeper, 'song', '5,12,88alert1', 'song,albumalert2')
            ->willReturn($dialog);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('withBody')->willReturnSelf();
        $this->responseFactory->method('createResponse')->willReturn($response);
        $this->streamFactory->method('createStream')->willReturn($this->createMock(StreamInterface::class));

        $this->subject->run($request, $this->gatekeeper);
    }

    protected function setUp(): void
    {
        $this->responseFactory    = $this->createMock(ResponseFactoryInterface::class);
        $this->streamFactory      = $this->createMock(StreamFactoryInterface::class);
        $this->configContainer    = $this->createMock(ConfigContainerInterface::class);
        $this->libraryItemLoader  = $this->createMock(LibraryItemLoaderInterface::class);
        $this->logger             = $this->createMock(LoggerInterface::class);
        $this->shareRepository    = $this->createMock(ShareRepositoryInterface::class);
        $this->browseFactory      = $this->createMock(BrowseFactoryInterface::class);
        $this->guiFactory         = $this->createMock(GuiFactoryInterface::class);
        $this->gatekeeper         = $this->createMock(GuiGatekeeperInterface::class);

        $this->subject = new ShowEditPlaylistAction(
            $this->responseFactory,
            $this->streamFactory,
            $this->configContainer,
            $this->libraryItemLoader,
            $this->logger,
            $this->shareRepository,
            $this->browseFactory,
            $this->guiFactory,
        );
    }
}
