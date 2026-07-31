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

namespace Ampache\Module\Util;

use PHPUnit\Framework\TestCase;

/**
 * NOTE: Environment has no constructor.
 */
class EnvironmentTest extends TestCase
{
    private Environment $subject;

    public function testCheckDependenciesFolderFindsVendorDirectory(): void
    {
        static::assertTrue($this->subject->check_dependencies_folder());
    }

    public function testCheckPhpHashAlgoSupportsSha256(): void
    {
        static::assertTrue($this->subject->check_php_hash_algo());
    }

    public function testCheckPhpHashIsAvailable(): void
    {
        static::assertTrue($this->subject->check_php_hash());
    }

    public function testCheckPhpIntlIsAvailable(): void
    {
        static::assertTrue($this->subject->check_php_intl());
    }

    public function testCheckPhpIntSizeIs64Bit(): void
    {
        static::assertTrue($this->subject->check_php_int_size());
    }

    public function testCheckPhpJsonIsAvailable(): void
    {
        static::assertTrue($this->subject->check_php_json());
    }

    public function testCheckPhpPdoIsAvailable(): void
    {
        static::assertTrue($this->subject->check_php_pdo());
    }

    public function testCheckPhpPdoMysqlReturnsABooleanBasedOnLoadedDrivers(): void
    {
        static::assertIsBool($this->subject->check_php_pdo_mysql());
    }

    public function testCheckPhpSimplexmlIsAvailable(): void
    {
        static::assertTrue($this->subject->check_php_simplexml());
    }

    public function testCheckPhpVersionPassesOnTheRequiredVersion(): void
    {
        static::assertTrue($this->subject->check_php_version());
    }

    public function testIsCliReturnsTrueUnderThePhpunitCliRunner(): void
    {
        static::assertTrue($this->subject->isCli());
    }

    protected function setUp(): void
    {
        $this->subject = new Environment();
    }
}
