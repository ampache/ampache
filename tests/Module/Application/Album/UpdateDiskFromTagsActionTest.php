<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\ModelFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class UpdateDiskFromTagsActionTest extends TestCase
{
    private ModelFactoryInterface&MockObject $modelFactory;

    private UiInterface&MockObject $ui;

    private ConfigContainerInterface&MockObject $configContainer;

    private UpdateDiskFromTagsAction $subject;

    protected function setUp(): void
    {
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);
        $this->ui              = $this->createMock(UiInterface::class);
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new UpdateDiskFromTagsAction(
            $this->modelFactory,
            $this->ui,
            $this->configContainer
        );
    }

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
        $userId      = 24;
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
            ->method('format');
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
        $this->ui->expects(static::once())
            ->method('show')
            ->with(
                'show_update_items.inc.php',
                [
                    'object_id' => $albumDiskId,
                    'catalog_id' => $catalogId,
                    'type' => 'album_disk',
                    'target_url' => sprintf(
                        '%s/albums.php?action=show_disk&album_disk=%d',
                        $webPath,
                        $albumDiskId
                    )
                ]
            );
        $this->ui->expects(static::once())
            ->method('showBoxBottom');
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        static::assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
