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
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FunctionCheckerTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private LoggerInterface&MockObject $logger;
    private FunctionChecker $subject;

    public function testCheckBatchDownloadReturnsDownloadFlagWhenAllowed(): void
    {
        $user = $this->createMock(User::class);
        $user->method('has_access')
            ->with(AccessLevelEnum::GUEST)
            ->willReturn(true);
        $GLOBALS['user'] = $user;

        $this->configContainer->method('isFeatureEnabled')
            ->willReturnMap([
                [ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD, true],
                [ConfigurationKeyEnum::DOWNLOAD, true],
            ]);

        static::assertTrue($this->subject->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD));
    }

    public function testCheckBatchDownloadReturnsFalseWhenNoGlobalUser(): void
    {
        unset($GLOBALS['user']);

        static::assertFalse($this->subject->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD));
    }

    public function testCheckBatchDownloadReturnsFalseWhenZipDownloadDisabled(): void
    {
        $user            = $this->createMock(User::class);
        $GLOBALS['user'] = $user;

        $this->configContainer->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD)
            ->willReturn(false);

        static::assertFalse($this->subject->check(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD));
    }

    public function testCheckDownloadReturnsFeatureFlag(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DOWNLOAD)
            ->willReturn(true);

        static::assertTrue($this->subject->check(AccessFunctionEnum::FUNCTION_DOWNLOAD));
    }

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);

        $this->subject = new FunctionChecker(
            $this->configContainer,
            $this->logger,
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['user']);
    }
}
