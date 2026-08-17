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
        self::assertTrue($this->subject->check_dependencies_folder());
    }

    public function testCheckPhpHashAlgoSupportsSha256(): void
    {
        self::assertTrue($this->subject->check_php_hash_algo());
    }

    public function testCheckPhpHashIsAvailable(): void
    {
        self::assertTrue($this->subject->check_php_hash());
    }

    public function testCheckPhpIntlIsAvailable(): void
    {
        self::assertTrue($this->subject->check_php_intl());
    }

    public function testCheckPhpIntSizeIs64Bit(): void
    {
        self::assertTrue($this->subject->check_php_int_size());
    }

    public function testCheckPhpJsonIsAvailable(): void
    {
        self::assertTrue($this->subject->check_php_json());
    }

    public function testCheckPhpPdoIsAvailable(): void
    {
        self::assertTrue($this->subject->check_php_pdo());
    }

    public function testCheckPhpPdoMysqlReturnsABooleanBasedOnLoadedDrivers(): void
    {
        self::assertIsBool($this->subject->check_php_pdo_mysql());
    }

    public function testCheckPhpSimplexmlIsAvailable(): void
    {
        self::assertTrue($this->subject->check_php_simplexml());
    }

    public function testCheckPhpVersionPassesOnTheRequiredVersion(): void
    {
        self::assertTrue($this->subject->check_php_version());
    }

    public function testExtensionListsMatchComposerJson(): void
    {
        $composer = json_decode(
            (string) file_get_contents(__DIR__ . '/../../../composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $extensions = static fn(array $packages): array => array_values(
            array_map(
                static fn(string $package): string => substr($package, 4),
                array_filter(
                    array_keys($packages),
                    static fn(string $package): bool => str_starts_with($package, 'ext-')
                )
            )
        );

        $required = $extensions($composer['require']);
        $optional = array_values(array_diff($extensions($composer['suggest']), $required));

        self::assertSame($required, Environment::REQUIRED_EXTENSIONS);
        self::assertSame($optional, array_keys(Environment::OPTIONAL_EXTENSIONS));
    }

    public function testGetExtensionStatusReportsEveryDeclaredExtension(): void
    {
        $status = $this->subject->getExtensionStatus();

        self::assertCount(
            count(Environment::REQUIRED_EXTENSIONS)
            + count(Environment::ADDITIONAL_EXTENSIONS)
            + count(Environment::OPTIONAL_EXTENSIONS),
            $status
        );

        $curl = $status[array_search('curl', array_column($status, 'name'), true)];

        self::assertTrue($curl['required']);
        self::assertTrue($curl['loaded']);
    }

    public function testIsCliReturnsTrueUnderThePhpunitCliRunner(): void
    {
        self::assertTrue($this->subject->isCli());
    }

    protected function setUp(): void
    {
        $this->subject = new Environment();
    }
}
