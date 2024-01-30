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
use Ampache\Repository\Model\License;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ServerRequestInterface;

class EditActionTest extends MockeryTestCase
{
    /** @var MockInterface|UiInterface|null */
    private MockInterface $ui;

    /** @var MockInterface|ConfigContainerInterface|null */
    private MockInterface $configContainer;

    /** @var MockInterface|ModelFactoryInterface|null */
    private MockInterface $modelFactory;

    /** @var MockInterface|LicenseRepositoryInterface|null */
    private MockInterface $licenseRepository;

    private ?EditAction $subject;

    protected function setUp(): void
    {
        $this->ui                = $this->mock(UiInterface::class);
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory      = $this->mock(ModelFactoryInterface::class);
        $this->licenseRepository = $this->mock(LicenseRepositoryInterface::class);

        $this->subject = new EditAction(
            $this->ui,
            $this->configContainer,
            $this->modelFactory,
            $this->licenseRepository
        );
    }

    public function testRunThrowsExceptionIfAccessIsDenied(): void
    {
        $this->expectException(AccessDeniedException::class);

        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnFalse();

        $this->subject->run(
            $request,
            $gatekeeper
        );
    }

    public function testRunUpdatesAndReturnsNull(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);
        $license    = $this->mock(License::class);

        $licenseId     = 666;
        $webPath       = 'some-path';
        $name          = 'some-name';
        $description   = 'some-description';
        $external_link = 'some-external-link';
        $data          = [
            'license_id' => (string) 666,
            'name' => $name,
            'description' => $description,
            'external_link' => $external_link,
        ];

        $this->licenseRepository->shouldReceive('update')
            ->with(
                $license,
                $name,
                $description,
                $external_link
            )
            ->once();

        $license->shouldReceive('isNew')
            ->withNoArgs()
            ->once()
            ->andReturnFalse();

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn($data);

        $this->modelFactory->shouldReceive('createLicense')
            ->with($licenseId)
            ->once()
            ->andReturn($license);

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                T_('No Problem'),
                'The License has been updated',
                sprintf('%s/admin/license.php', $webPath)
            )
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

    public function testRunCreatesAndReturnsNull(): void
    {
        $request    = $this->mock(ServerRequestInterface::class);
        $gatekeeper = $this->mock(GuiGatekeeperInterface::class);

        $name        = 'some-name';
        $description = 'some-description';
        $data        = ['name' => $name, 'description' => $description];
        $webPath     = 'some-path';

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $gatekeeper->shouldReceive('mayAccess')
            ->with(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getParsedBody')
            ->withNoArgs()
            ->once()
            ->andReturn($data);

        $this->licenseRepository->shouldReceive('create')
            ->with($name, $description, '')
            ->once();

        $this->ui->shouldReceive('showHeader')
            ->withNoArgs()
            ->once();
        $this->ui->shouldReceive('showConfirmation')
            ->with(
                T_('No Problem'),
                'A new License has been created',
                sprintf('%s/admin/license.php', $webPath)
            )
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
}
