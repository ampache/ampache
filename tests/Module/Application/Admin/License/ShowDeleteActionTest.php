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

namespace Ampache\Module\Application\Admin\License;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Application\Exception\ObjectNotFoundException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\License;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class ShowDeleteActionTest extends MockeryTestCase
{
    private MockInterface&ConfigContainerInterface $configContainer;
    private MockInterface&LicenseRepositoryInterface $licenseRepository;
    private ShowDeleteAction $subject;
    private MockInterface&UiInterface $ui;

    public function testRunShowsConfirmationWithFormToken(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $item       = $this->mock(License::class);

        $itemId = 666;
        $label  = 'some-label';

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getWebPath')
            ->with('/admin')
            ->once()
            ->andReturn('/admin');

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['license_id' => (string) $itemId]);

        $this->licenseRepository->shouldReceive('findById')
            ->with($itemId)
            ->once()
            ->andReturn($item);

        $item->shouldReceive('getName')
            ->withNoArgs()
            ->once()
            ->andReturn($label);

        $this->ui->shouldReceive('showHeader')->withNoArgs()->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                'Are You Sure?',
                sprintf('This will permanently delete the license "%s"', $label),
                sprintf('/admin/license.php?action=delete&license_id=%d', $itemId),
                1,
                'delete_license'
            )
            ->once();
        $this->ui->shouldReceive('showQueryStats')->withNoArgs()->once();
        $this->ui->shouldReceive('showFooter')->withNoArgs()->once();

        static::assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunShowsNothingInDemoMode(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnTrue();

        $this->ui->shouldReceive('showHeader')->withNoArgs()->once();
        $this->ui->shouldReceive('showQueryStats')->withNoArgs()->once();
        $this->ui->shouldReceive('showFooter')->withNoArgs()->once();
        $this->ui->shouldNotReceive('showConfirmation');

        static::assertNull(
            $this->subject->run($request, $gatekeeper)
        );
    }

    public function testRunThrowsIfAccessIsDenied(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        static::expectException(AccessDeniedException::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->once()
            ->andReturnFalse();

        $this->subject->run($request, $gatekeeper);
    }

    public function testRunThrowsIfItemWasNotFound(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $itemId = 666;

        static::expectException(ObjectNotFoundException::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)
            ->once()
            ->andReturnTrue();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->once()
            ->andReturnFalse();

        $this->ui->shouldReceive('showHeader')->withNoArgs()->once();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn(['license_id' => (string) $itemId]);

        $this->licenseRepository->shouldReceive('findById')
            ->with($itemId)
            ->once()
            ->andReturnNull();

        $this->subject->run($request, $gatekeeper);
    }

    protected function setUp(): void
    {
        $this->ui                = $this->mock(UiInterface::class);
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->licenseRepository = $this->mock(LicenseRepositoryInterface::class);

        $this->subject = new ShowDeleteAction(
            $this->ui,
            $this->configContainer,
            $this->licenseRepository
        );
    }
}
