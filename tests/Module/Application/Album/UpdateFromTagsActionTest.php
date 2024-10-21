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
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateFromTagsActionTest extends MockeryTestCase
{
    private ModelFactoryInterface&MockInterface $modelFactory;

    private UiInterface&MockInterface $ui;

    private ConfigContainerInterface&MockInterface $configContainer;

    private UpdateFromTagsAction $subject;

    protected function setUp(): void
    {
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->ui              = $this->mock(UiInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);

        $this->subject = new UpdateFromTagsAction(
            $this->modelFactory,
            $this->ui,
            $this->configContainer
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $albumId = 666;
        $userId  = 24;

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['album_id' => (string) $albumId]);

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);

        $album->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $album->shouldReceive('get_user_owner')
            ->withNoArgs()
            ->once()
            ->andReturnNull();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunRenders(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $album      = $this->mock(Album::class);

        $albumId   = 666;
        $userId    = 24;
        $catalogId = 42;
        $webPath   = 'some-web-path';


        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['album_id' => (string) $albumId]);

        $this->modelFactory->shouldReceive('createAlbum')
            ->with($albumId)
            ->once()
            ->andReturn($album);

        $album->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)
            ->once()
            ->andReturnFalse();

        $gatekeeper->shouldReceive('getUserId')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $album->shouldReceive('get_user_owner')
            ->withNoArgs()
            ->once()
            ->andReturn($userId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $album->shouldReceive('format')
            ->withNoArgs()
            ->once();
        $album->shouldReceive('getCatalogId')
            ->withNoArgs()
            ->once()
            ->andReturn($catalogId);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_update_items.inc.php',
                [
                    'object_id' => $albumId,
                    'catalog_id' => $catalogId,
                    'type' => 'album',
                    'target_url' => sprintf(
                        '%s/albums.php?action=show&album=%d',
                        $webPath,
                        $albumId
                    )
                ]
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function mock($className)
    {
        return parent::mock($className); // TODO: Change the autogenerated stub
    }
}
