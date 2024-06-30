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

namespace Ampache\Module\Application\Admin\Export;

use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Catalog\CatalogLoaderInterface;
use Ampache\Module\Catalog\Exception\CatalogLoadingException;
use Ampache\Module\Catalog\Export\CatalogExportFactoryInterface;
use Ampache\Module\Catalog\Export\CatalogExporterInterface;
use Ampache\Module\Catalog\Export\CatalogExportTypeEnum;
use Ampache\Repository\Model\Catalog;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ExportActionTest extends TestCase
{
    private CatalogExportFactoryInterface&MockObject $catalogExportFactory;

    private CatalogLoaderInterface&MockObject $catalogLoader;

    private ExportAction $subject;

    private ServerRequestInterface&MockObject $request;

    private GuiGatekeeperInterface&MockObject $gatekeeper;

    protected function setUp(): void
    {
        $this->catalogExportFactory = $this->createMock(CatalogExportFactoryInterface::class);
        $this->catalogLoader        = $this->createMock(CatalogLoaderInterface::class);
        $this->request              = $this->createMock(ServerRequestInterface::class);
        $this->gatekeeper           = $this->createMock(GuiGatekeeperInterface::class);

        $this->subject = new ExportAction(
            $this->catalogExportFactory,
            $this->catalogLoader,
        );
    }

    public function testRunFailsIfAccessIsDenied(): void
    {
        static::expectException(AccessDeniedException::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(false);

        $this->subject->run($this->request, $this->gatekeeper);
    }

    #[RunInSeparateProcess]
    public function testRunExportsEverythingIfCatalogIsNotDefined(): void
    {
        $exporter = $this->createMock(CatalogExporterInterface::class);

        ob_start();

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $this->catalogLoader->expects(static::once())
            ->method('getById')
            ->with(0)
            ->willThrowException(new CatalogLoadingException());

        $this->catalogExportFactory->expects(static::once())
            ->method('createFromExportType')
            ->with(CatalogExportTypeEnum::CSV)
            ->willReturn($exporter);

        $exporter->expects(static::once())
            ->method('sendHeaders');
        $exporter->expects(static::once())
            ->method('export')
            ->with(null);

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }

    #[RunInSeparateProcess]
    public function testRunExportsCatalog(): void
    {
        ob_start();

        $catalogId    = 666;
        $exportFormat = CatalogExportTypeEnum::ITUNES;

        $catalog  = $this->createMock(Catalog::class);
        $exporter = $this->createMock(CatalogExporterInterface::class);

        $this->gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $this->request->expects(static::once())
            ->method('getParsedBody')
            ->willReturn([
                'export_catalog' => (string) $catalogId,
                'export_format' => $exportFormat->value,
            ]);

        $this->catalogLoader->expects(static::once())
            ->method('getById')
            ->with($catalogId)
            ->willReturn($catalog);

        $this->catalogExportFactory->expects(static::once())
            ->method('createFromExportType')
            ->with($exportFormat)
            ->willReturn($exporter);

        $exporter->expects(static::once())
            ->method('sendHeaders');
        $exporter->expects(static::once())
            ->method('export')
            ->with($catalog);

        static::assertNull(
            $this->subject->run($this->request, $this->gatekeeper)
        );
    }
}
