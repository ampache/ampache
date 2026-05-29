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

namespace Ampache\Config;

use Ampache\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ConfigContainerTest extends MockeryTestCase
{
    public function testGetReturnsValue(): void
    {
        $key   = 'some-key';
        $value = 'some-value';

        $subject = $this->createSubject([$key => $value]);

        self::assertSame(
            $value,
            $subject->get($key)
        );
    }

    public function testUpdateConfigMergesInternalConfigArray(): void
    {
        $existing_key   = 'some-existing-key';
        $existing_value = 'some-exsiting-value';
        $key            = 'some-key';
        $old_value      = 'some-old-value';
        $value          = 'some-value';
        $config_data    = [$key => $value];

        $config = $this->createSubject([$existing_key => $existing_value, $key => $old_value]);

        self::assertSame(
            $old_value,
            $config->get($key)
        );

        self::assertSame(
            $config,
            $config->updateConfig($config_data)
        );

        self::assertSame(
            $value,
            $config->get($key)
        );
        self::assertSame(
            $existing_value,
            $config->get($existing_key)
        );
    }

    public function testGetReturnsNullIfKeyNotSet(): void
    {
        self::assertNull(
            $this->createSubject([])->get('foobar')
        );
    }

    public function testGetSessionNameReturnsValue(): void
    {
        $value = 'some-value';

        $subject = $this->createSubject([
            ConfigurationKeyEnum::SESSION_NAME => $value
        ]);

        self::assertSame(
            $value,
            $subject->getSessionName()
        );
    }

    public function testGetSessionNameReturnsEmptyStringIfNotSet(): void
    {
        self::assertSame(
            '',
            $this->createSubject()->getSessionName()
        );
    }

    public function testIsWebDavEnabledReturnsValueCasted(): void
    {
        self::assertTrue(
            $this->createSubject([ConfigurationKeyEnum::BACKEND_WEBDAV => '1'])->isWebDavBackendEnabled()
        );
    }

    public function testIsWebDavEnabledReturnsDefault(): void
    {
        self::assertFalse(
            $this->createSubject()->isWebDavBackendEnabled()
        );
    }

    public function testIsAuthenticationEnabledReturnsValueCasted(): void
    {
        self::assertFalse(
            $this->createSubject([ConfigurationKeyEnum::USE_AUTH => '0'])->isAuthenticationEnabled()
        );
    }

    public function testIsAuthenticationEnabledReturnsDefault(): void
    {
        self::assertTrue(
            $this->createSubject()->isAuthenticationEnabled()
        );
    }

    public function testGetRawWebPathReturnsConfigValue(): void
    {
        $value = 'some-path';

        self::assertSame(
            $value,
            $this->createSubject([ConfigurationKeyEnum::RAW_WEB_PATH => $value])->getRawWebPath()
        );
    }

    public function testGetRawWebPathReturnsDefault(): void
    {
        self::assertSame(
            '',
            $this->createSubject()->getRawWebPath()
        );
    }

    public function testGetWebPathReturnsPath(): void
    {
        $value = 'some-path';

        self::assertSame(
            $value,
            $this->createSubject([ConfigurationKeyEnum::WEB_PATH => $value])->getWebPath()
        );
    }

    public function testGetWebPathReturnsDefault(): void
    {
        self::assertSame(
            '',
            $this->createSubject([])->getWebPath()
        );
    }

    public function testGetTypesAllowedForZipReturnsEmptyArrayIfNotSet(): void
    {
        self::assertSame(
            [],
            $this->createSubject([])->getTypesAllowedForZip()
        );
    }

    public function testGetTypesAllowedForZipReturnsValues(): void
    {
        $type1 = ' some-type';
        $type2 = 'some-other-type ';

        self::assertSame(
            [trim($type1), trim($type2)],
            $this->createSubject([
                ConfigurationKeyEnum::ALLOWED_ZIP_TYPES => $type1 . ',' . $type2
            ])->getTypesAllowedForZip()
        );
    }

    #[DataProvider(methodName: 'featureEnabledDataProvider')]
    public function testIsFeatureEnabledReturnsExpectedState(
        $value,
        bool $state
    ): void {
        $key = 'some-key';

        self::assertSame(
            $state,
            $this->createSubject([
                $key => $value
            ])->isFeatureEnabled($key)
        );
    }

    public static function featureEnabledDataProvider(): array
    {
        return [
            [true, true],
            ['true', true],
            [1, true],
            ['1', true],
            ['0', false],
            ['', false],
            ['false', false],
            [0, false],
        ];
    }

    public function testGetThemePathReturnsValue(): void
    {
        $value = 'some-path';

        self::assertSame(
            $value,
            $this->createSubject([ConfigurationKeyEnum::THEME_PATH => $value])->getThemePath()
        );
    }

    public function testIsDebugModeReturnsValue(): void
    {
        $this->assertFalse(
            $this->createSubject([])->isDebugMode()
        );
    }

    public function testIsDemoModeReturnsValue(): void
    {
        $this->assertFalse(
            $this->createSubject([])->isDemoMode()
        );
    }

    public function testGetConfigFilePathReturnsPath(): void
    {
        $this->assertStringContainsString(
            '/config/ampache.cfg.php',
            $this->createSubject([])->getConfigFilePath()
        );
    }

    public function testGetComposerBinaryPathReturnsDefault(): void
    {
        $this->assertSame(
            'composer',
            $this->createSubject([])->getComposerBinaryPath()
        );
    }

    public function testGetNpmBinaryPathReturnsDefault(): void
    {
        $this->assertSame(
            'npm',
            $this->createSubject([])->getNpmBinaryPath()
        );
    }

    public function testGetComposerBinaryPathReturnsValue(): void
    {
        $value = 'some-value';

        $this->assertSame(
            $value,
            $this->createSubject([
                ConfigurationKeyEnum::COMPOSER_BINARY_PATH => $value
            ])->getComposerBinaryPath()
        );
    }

    public function testGetNpmBinaryPathReturnsValue(): void
    {
        $value = 'some-value';

        $this->assertSame(
            $value,
            $this->createSubject([
                ConfigurationKeyEnum::NPM_BINARY_PATH => $value
            ])->getNpmBinaryPath()
        );
    }

    public function testGetVersionReturnsVersion(): void
    {
        $value = 'some-value';

        $this->assertSame(
            $value,
            $this->createSubject([
                ConfigurationKeyEnum::VERSION => $value
            ])->getVersion()
        );
    }

    private function createSubject(array $configuration = []): ConfigContainerInterface
    {
        return new ConfigContainer($configuration);
    }
}
