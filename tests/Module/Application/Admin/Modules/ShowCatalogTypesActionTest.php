<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Application\Admin\Modules;

use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\Catalog;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowCatalogTypesActionTest extends MockeryTestCase
{
    private MockInterface $ui;

    private MockInterface $catalogLoader;

    private ShowCatalogTypesAction $subject;

    public function setUp(): void
    {
        $this->ui            = $this->mock(UiInterface::class);
        $this->catalogLoader = $this->mock(CatalogLoaderInterface::class);

        $this->subject = new ShowCatalogTypesAction(
            $this->ui,
            $this->catalogLoader
        );
    }

    public function testRunThrowsAccessDeniedExceptionIfNotAdmin(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $this->expectException(AccessDeniedException::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunRendersCatalogTypes(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showBoxTop')
            ->with('Catalog Types', 'box box_catalog_types')
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_catalog_types.inc.php',
                [
                    'catalogs' => Catalog::get_catalog_types(),
                    'catalogLoader' => $this->catalogLoader
                ]
            )
            ->once();
        $this->ui->shouldReceive('showBoxBottom')
            ->withNoArgs()
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
}
