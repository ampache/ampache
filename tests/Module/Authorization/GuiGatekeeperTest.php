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

namespace Ampache\Module\Authorization;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Mockery\MockInterface;
use Override;

class GuiGatekeeperTest extends MockeryTestCase
{
    private MockInterface&ConfigContainerInterface $configContainer;
    private MockInterface&PrivilegeCheckerInterface $privilegeChecker;
    private GuiGatekeeper $subject;

    public function testMayAccessPerformsPrivilegeCheck(): void
    {
        $type  = AccessTypeEnum::API;
        $level = AccessLevelEnum::ADMIN;

        $this->privilegeChecker->shouldReceive('check')
            ->with($type, $level)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->mayAccess($type, $level)
        );
    }

    public function testMayAdministerIsRefusedInDemoMode(): void
    {
        // demo mode answers every privilege check with true, so the level alone would let a visitor write
        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->andReturnTrue();

        $this->assertFalse($this->subject->mayAdminister());
    }

    public function testMayAdministerIsRefusedWithoutTheLevel(): void
    {
        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->andReturnFalse();
        $this->configContainer->shouldReceive('isFeatureEnabled')->andReturnFalse();

        $this->assertFalse($this->subject->mayAdminister());
    }

    public function testMayAdministerNeedsTheLevelAndNoDemoMode(): void
    {
        $this->privilegeChecker->shouldReceive('check')
            ->with(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)
            ->andReturnTrue();
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->andReturnFalse();

        $this->assertTrue($this->subject->mayAdminister());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->privilegeChecker = $this->mock(PrivilegeCheckerInterface::class);
        $this->configContainer  = $this->mock(ConfigContainerInterface::class);
        $this->configContainer->shouldReceive('isFeatureEnabled')->andReturnFalse()->byDefault();

        $this->subject = new GuiGatekeeper(
            $this->privilegeChecker,
            $this->configContainer
        );
    }
}
