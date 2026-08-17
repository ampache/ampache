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
use Ampache\Repository\AccessRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NetworkCheckerTest extends TestCase
{
    private AccessRepositoryInterface&MockObject $accessRepository;
    private ConfigContainerInterface&MockObject $configContainer;
    private NetworkChecker $subject;

    public function testCheckAllowsInterfaceAndStreamWhenAccessControlDisabled(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ACCESS_CONTROL)
            ->willReturn(false);

        $this->accessRepository->expects(static::never())
            ->method('findByIp');

        self::assertTrue($this->subject->check(AccessTypeEnum::INTERFACE));
        self::assertTrue($this->subject->check(AccessTypeEnum::STREAM));
        self::assertFalse($this->subject->check(AccessTypeEnum::API));
    }

    public function testCheckDelegatesToAccessRepositoryWhenAccessControlEnabled(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ACCESS_CONTROL)
            ->willReturn(true);

        $this->accessRepository->expects(static::once())
            ->method('findByIp')
            ->with(
                self::isType('string'),
                AccessLevelEnum::USER,
                AccessTypeEnum::INTERFACE,
                21,
            )
            ->willReturn(true);

        self::assertTrue($this->subject->check(AccessTypeEnum::INTERFACE, 21));
    }

    public function testCheckReturnsFalseForUnsupportedTypeWhenAccessControlEnabled(): void
    {
        $this->configContainer->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ACCESS_CONTROL)
            ->willReturn(true);

        $this->accessRepository->expects(static::never())
            ->method('findByIp');

        self::assertFalse($this->subject->check(AccessTypeEnum::LOCALPLAY));
    }

    protected function setUp(): void
    {
        $this->configContainer  = $this->createMock(ConfigContainerInterface::class);
        $this->accessRepository = $this->createMock(AccessRepositoryInterface::class);

        $this->subject = new NetworkChecker(
            $this->configContainer,
            $this->accessRepository,
        );
    }
}
