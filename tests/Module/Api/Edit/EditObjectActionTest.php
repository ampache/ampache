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

namespace Ampache\Module\Api\Edit;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\LibraryItemLoaderInterface;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShareRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class EditObjectActionTest extends TestCase
{
    private BrowseFactoryInterface&MockObject $browseFactory;
    private ConfigContainerInterface&MockObject $configContainer;
    private LabelRepositoryInterface&MockObject $labelRepository;
    private LibraryItemLoaderInterface&MockObject $libraryItemLoader;
    private LoggerInterface&MockObject $logger;
    private ResponseFactoryInterface&MockObject $responseFactory;
    private ShareRepositoryInterface&MockObject $shareRepository;
    private StreamFactoryInterface&MockObject $streamFactory;
    private EditObjectAction $subject;

    public function testRunLoadsATypeThatCarriesNoRowSuffix(): void
    {
        $libitem = $this->createMock(library_item::class);

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::SONG, 666)
            ->willReturn($libitem);

        $libitem->expects(static::once())
            ->method('update')
            ->willReturn(666);

        $this->subject->run(
            $this->createRequest(['type' => 'song', 'id' => '666'], ['id' => '666']),
            $this->createGatekeeper()
        );
    }

    public function testRunLoadsTheItemThroughTheLoaderAndSavesTheScrubbedPostData(): void
    {
        $libitem    = $this->createMock(library_item::class);
        $gatekeeper = $this->createGatekeeper();

        $this->libraryItemLoader->expects(static::once())
            ->method('load')
            ->with(LibraryItemEnum::SONG, 666)
            ->willReturn($libitem);

        // the whole point of the action: whatever the form posted has to reach the model's update() untouched
        $libitem->expects(static::once())
            ->method('update')
            ->with(self::callback(static fn(array $data): bool => $data['title'] === 'some-title'))
            ->willReturn(666);

        $response = $this->subject->run(
            $this->createRequest(['type' => 'song_row', 'id' => '666'], ['id' => '666', 'title' => 'some-title']),
            $gatekeeper
        );

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testRunResolvesShareThroughItsOwnRepository(): void
    {
        // `share` is editable but is not a library_item, so the loader can never serve it and must not be asked
        $share = $this->createMock(Share::class);
        $user  = $this->createMock(User::class);

        $this->libraryItemLoader->expects(static::never())->method('load');
        $this->shareRepository->expects(static::once())
            ->method('findById')
            ->with(666)
            ->willReturn($share);

        $share->expects(static::once())
            ->method('update')
            ->with(self::isType('array'), $user)
            ->willReturn(true);

        $this->subject->run(
            $this->createRequest(['type' => 'share_row', 'id' => '666'], ['id' => '666']),
            $this->createGatekeeper($user)
        );
    }

    public function testRunReturnsNullInDemoMode(): void
    {
        $libitem = $this->createMock(library_item::class);

        $this->libraryItemLoader->method('load')->willReturn($libitem);
        $libitem->expects(static::never())->method('update');

        $this->configContainer->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(true);

        self::assertNull(
            $this->subject->run(
                $this->createRequest(['type' => 'song_row', 'id' => '666'], ['id' => '666']),
                $this->createGatekeeper()
            )
        );
    }

    public function testRunReturnsNullWhenNoIdWasPosted(): void
    {
        $libitem = $this->createMock(library_item::class);

        $this->libraryItemLoader->method('load')->willReturn($libitem);
        $libitem->expects(static::never())->method('update');

        self::assertNull(
            $this->subject->run(
                $this->createRequest(['type' => 'song_row', 'id' => '666'], ['title' => 'some-title']),
                $this->createGatekeeper()
            )
        );
    }

    public function testRunReturnsNullWhenTheItemDoesNotExist(): void
    {
        $this->libraryItemLoader->method('load')->willReturn(null);

        self::assertNull(
            $this->subject->run(
                $this->createRequest(['type' => 'song_row', 'id' => '666'], ['id' => '666']),
                $this->createGatekeeper()
            )
        );
    }

    public function testRunReturnsNullWhenTheUserMayNotEdit(): void
    {
        $libitem = $this->createMock(library_item::class);

        $this->libraryItemLoader->method('load')->willReturn($libitem);
        $libitem->expects(static::never())->method('update');

        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $gatekeeper->method('getUser')->willReturn(null);
        $gatekeeper->method('mayAccess')->willReturn(false);

        self::assertNull(
            $this->subject->run(
                $this->createRequest(['type' => 'song_row', 'id' => '666'], ['id' => '666']),
                $gatekeeper
            )
        );
    }

    public function testRunReturnsNullWithoutLoadingWhenTheTypeIsNotEditable(): void
    {
        $this->libraryItemLoader->expects(static::never())->method('load');
        $this->shareRepository->expects(static::never())->method('findById');

        self::assertNull(
            $this->subject->run(
                $this->createRequest(['type' => 'catalog_row', 'id' => '666'], ['id' => '666']),
                $this->createGatekeeper()
            )
        );
    }

    public function testRunStripsMarkupFromThePostedValues(): void
    {
        $libitem = $this->createMock(library_item::class);

        $this->libraryItemLoader->method('load')->willReturn($libitem);

        $captured = null;
        $libitem->expects(static::once())
            ->method('update')
            ->with(self::callback(static function (array $data) use (&$captured): bool {
                $captured = $data;

                return true;
            }))
            ->willReturn(666);

        $this->subject->run(
            $this->createRequest(
                ['type' => 'song_row', 'id' => '666'],
                ['id' => '666', 'title' => '<script>alert(1)</script>']
            ),
            $this->createGatekeeper()
        );

        self::assertIsArray($captured);
        self::assertStringNotContainsString('<script>', (string) $captured['title']);
    }

    protected function setUp(): void
    {
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);
        $this->browseFactory     = $this->createMock(BrowseFactoryInterface::class);
        $this->labelRepository   = $this->createMock(LabelRepositoryInterface::class);
        $this->libraryItemLoader = $this->createMock(LibraryItemLoaderInterface::class);
        $this->logger            = $this->createMock(LoggerInterface::class);
        $this->responseFactory   = $this->createMock(ResponseFactoryInterface::class);
        $this->shareRepository   = $this->createMock(ShareRepositoryInterface::class);
        $this->streamFactory     = $this->createMock(StreamFactoryInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('withHeader')->willReturnSelf();
        $response->method('withBody')->willReturnSelf();
        $this->responseFactory->method('createResponse')->willReturn($response);
        $this->streamFactory->method('createStream')->willReturn($this->createMock(StreamInterface::class));

        $this->subject = new EditObjectAction(
            $this->configContainer,
            $this->labelRepository,
            $this->libraryItemLoader,
            $this->logger,
            $this->responseFactory,
            $this->shareRepository,
            $this->browseFactory,
            $this->streamFactory
        );
    }

    private function createGatekeeper(?User $user = null): GuiGatekeeperInterface&MockObject
    {
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $gatekeeper->method('getUser')->willReturn($user);
        $gatekeeper->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, self::isInstanceOf(AccessLevelEnum::class))
            ->willReturn(true);

        return $gatekeeper;
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $body
     */
    private function createRequest(array $query, array $body): ServerRequestInterface&MockObject
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($query);
        $request->method('getParsedBody')->willReturn($body);

        return $request;
    }
}
