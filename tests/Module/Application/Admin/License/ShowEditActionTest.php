<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Config\ConfigContainerInterface;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\License;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Mockery\MockInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

class ShowEditActionTest extends MockeryTestCase
{
    private MockInterface&UiInterface $ui;

    private LicenseRepositoryInterface&MockObject $licenseRepository;

    private ConfigContainerInterface&MockObject $configContainer;

    private ShowEditAction $subject;

    protected function setUp(): void
    {
        $this->ui                = $this->mock(UiInterface::class);
        $this->licenseRepository = $this->createMock(LicenseRepositoryInterface::class);
        $this->configContainer   = $this->createMock(ConfigContainerInterface::class);

        $this->subject = new ShowEditAction(
            $this->ui,
            $this->licenseRepository,
            $this->configContainer,
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->once()
            ->andReturnFalse();

        $this->subject->run(
            $request,
            $gatekeeper
        );
    }

    public function testRunRendersAndReturnsNull(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $license    = $this->mock(License::class);

        $licenseId = 666;
        $webPath   = 'some-path';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['license_id' => (string) $licenseId]);

        $this->licenseRepository->expects(static::once())
            ->method('findById')
            ->with($licenseId)
            ->willReturn($license);

        $this->configContainer->expects(static::once())
            ->method('getWebPath')
            ->willReturn($webPath);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showBoxTop')
            ->with('Edit license')
            ->once();
        $this->ui->shouldReceive('show')
            ->with(
                'show_edit_license.inc.php',
                [
                    'license' => $license,
                    'webPath' => $webPath,
                ]
            )
            ->once();
        $this->ui->shouldReceive('showBoxBottom')
            ->once();
        $this->ui->shouldReceive('showQueryStats')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showFooter')
            ->withNoArgs()
            ->once();

        $this->assertNull(
            $this->subject->run(
                $request,
                $gatekeeper
            )
        );
    }

    public function testRunErrorsIfLicenseWasNotFound(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        static::expectException(ObjectNotFoundException::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->licenseRepository->expects(static::once())
            ->method('findById')
            ->with(0)
            ->willReturn(null);

        $this->subject->run(
            $request,
            $gatekeeper
        );
    }
}
