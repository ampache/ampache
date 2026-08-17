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
 */

namespace Ampache\Module\Authorization\Check;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrivilegeCheckerTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private ModelFactoryInterface&MockObject $modelFactory;
    private PrivilegeChecker $subject;

    public function testCheckAllowsEverythingInDemoMode(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DEMO_MODE)
            ->willReturn(true);

        $this->modelFactory->expects(static::never())
            ->method('createUser');

        self::assertTrue($this->subject->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN));
    }

    public function testCheckInterfaceComparesUserAccessToRequiredLevel(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->willReturn(false);

        $user         = $this->createMock(User::class);
        $user->id     = 21;
        $user->access = AccessLevelEnum::ADMIN->value;

        $this->modelFactory->method('createUser')
            ->with(21)
            ->willReturn($user);

        self::assertTrue($this->subject->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER, 21));
    }

    public function testCheckReturnsFalseForUnsupportedType(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->willReturn(false);

        $user         = $this->createMock(User::class);
        $user->id     = 21;
        $user->access = AccessLevelEnum::ADMIN->value;

        $this->modelFactory->method('createUser')
            ->willReturn($user);

        self::assertFalse($this->subject->check(AccessTypeEnum::API, AccessLevelEnum::USER, 21));
    }

    public function testCheckReturnsFalseWhenUserIdResolvesToNoOne(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->willReturn(false);

        $user     = $this->createMock(User::class);
        $user->id = 0;

        $this->modelFactory->expects(static::once())
            ->method('createUser')
            ->with(21)
            ->willReturn($user);

        self::assertFalse($this->subject->check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER, 21));
    }

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->createMock(ModelFactoryInterface::class);

        $this->subject = new PrivilegeChecker(
            $this->configContainer,
            $this->modelFactory,
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['user']);
    }
}
