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
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\SingleItemUpdaterInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\ModelFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class UpdateDiskFromTagsActionTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private ModelFactoryInterface&MockObject $modelFactory;
    private SingleItemUpdaterInterface&MockObject $singleItemUpdater;
    private UpdateDiskFromTagsAction $subject;
    private UiInterface&MockObject $ui;

    public function testRunErrorsIfAccessIsDenied(): void
    {
        $request    = $this->createMock(ServerRequestInterface::class);
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->willReturn(false);

        static::expectException(AccessDeniedException::class);

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunRenders(): void
    {
        $request    = $this->createMock(ServerRequestInterface::class);
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $albumDisk  = $this->createMock(AlbumDisk::class);

        $albumDiskId = 666;
        $webPath     = 'some-web-path';
        $catalogId   = 123;

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->willReturn(true);

        $request->expects(static::once())
            ->method('getQueryParams')
            ->willReturn(['album_disk' => (string) $albumDiskId]);

        $this->modelFactory->expects(static::once())
            ->method('createAlbumDisk')
            ->with($albumDiskId)
            ->willReturn($albumDisk);

        $albumDisk->expects(static::once())
            ->method('isNew')
            ->willReturn(false);

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->willReturn(true);

        $albumDisk->expects(static::once())
            ->method('getCatalogId')
            ->willReturn($catalogId);

        $this->configContainer->expects(static::once())
            ->method('getWebPath')
            ->willReturn($webPath);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showBoxTop')
            ->with('Starting Update from Tags', 'box box_update_items');
        $this->singleItemUpdater->expects(static::once())
            ->method('update')
            ->willReturn('some-target-url');
        $this->ui->expects(static::once())
            ->method('showBoxBottom');
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        ob_start();

        try {
            $result = $this->subject->run($request, $gatekeeper);
        } finally {
            $output = (string) ob_get_clean();
        }

        self::assertNull($result);
        self::assertNotSame('', $output);
    }

    protected function setUp(): void
    {
        $this->modelFactory      = $this->createMock(ModelFactoryInterface::class);
        $this->ui                = $this->createMock(UiInterface::class);
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);
        $this->singleItemUpdater = $this->createMock(SingleItemUpdaterInterface::class);

        $this->subject = new UpdateDiskFromTagsAction(
            $this->modelFactory,
            $this->ui,
            $this->configContainer,
            $this->singleItemUpdater
        );
    }
}
