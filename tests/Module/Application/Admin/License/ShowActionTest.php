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

namespace Ampache\Module\Application\Admin\License;

use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use ArrayIterator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

class ShowActionTest extends MockeryTestCase
{
    private UiInterface&MockObject $ui;

    private MockObject&ModelFactoryInterface $modelFactory;

    private LicenseRepositoryInterface&MockObject $licenseRepository;

    private ShowAction $subject;

    protected function setUp(): void
    {
        $this->ui                = $this->createMock(UiInterface::class);
        $this->modelFactory      = $this->createMock(ModelFactoryInterface::class);
        $this->licenseRepository = $this->createMock(LicenseRepositoryInterface::class);

        $this->subject = new ShowAction(
            $this->ui,
            $this->modelFactory,
            $this->licenseRepository,
        );
    }

    public function testThrowsExceptionIfAccessIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunShowsAndReturnsNull(): void
    {
        $request    = $this->createMock(ServerRequestInterface::class);
        $gatekeeper = $this->createMock(GuiGatekeeperInterface::class);
        $browse     = $this->createMock(Browse::class);

        $id          = 666;
        $name        = 'some-name';

        $gatekeeper->expects(static::once())
            ->method('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->willReturn(true);

        $this->ui->expects(static::once())
            ->method('showHeader');
        $this->ui->expects(static::once())
            ->method('showQueryStats');
        $this->ui->expects(static::once())
            ->method('showFooter');

        $this->modelFactory->expects(static::once())
            ->method('createBrowse')
            ->willReturn($browse);

        $this->licenseRepository->expects(static::once())
            ->method('getList')
            ->willReturn(new ArrayIterator([$id => $name]));

        $browse->expects(static::once())
            ->method('set_type')
            ->with('license');
        $browse->expects(static::once())
            ->method('set_simple_browse')
            ->with(true);
        $browse->expects(static::once())
            ->method('show_objects')
            ->with([$id]);
        $browse->expects(static::once())
            ->method('store');

        $this->assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }
}
