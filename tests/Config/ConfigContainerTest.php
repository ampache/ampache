<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Config;

use Ampache\MockeryTestCase;

class ConfigContainerTest extends MockeryTestCase
{
    public function testGetReturnsValue(): void
    {
        $key   = 'some-key';
        $value = 'some-value';

        $subject = $this->createSubject([$key => $value]);

        static::assertSame(
            $value,
            $subject->get($key)
        );
    }

    public function testUpdateConfigReplacesInternalConfigArray(): void
    {
        $key         = 'some-key';
        $value       = 'some-value';
        $config_data = [$key => $value];

        $config = $this->createSubject();

        static::assertSame(
            $config,
            $config->updateConfig($config_data)
        );

        static::assertSame(
            $value,
            $config->get($key)
        );
    }

    public function testGetReturnsNullIfKeyNotSet(): void
    {
        static::assertNull(
            $this->createSubject([])->get('foobar')
        );
    }

    public function testGetSessionNameReturnsValue(): void
    {
        $value = 'some-value';

        $subject = $this->createSubject([
            ConfigurationKeyEnum::SESSION_NAME => $value
        ]);

        static::assertSame(
            $value,
            $subject->getSessionName()
        );
    }

    public function testGetSessionNameReturnsEmptyStringIfNotSet(): void
    {
        static::assertSame(
            '',
            $this->createSubject()->getSessionName()
        );
    }

    public function testIsWebDavEnabledReturnsValueCasted(): void
    {
        static::assertTrue(
            $this->createSubject([ConfigurationKeyEnum::BACKEND_WEBDAV => '1'])->isWebDavBackendEnabled()
        );
    }

    public function testIsWebDavEnabledReturnsDefault(): void
    {
        static::assertFalse(
            $this->createSubject()->isWebDavBackendEnabled()
        );
    }

    public function testIsAuthenticationEnabledReturnsValueCasted(): void
    {
        static::assertFalse(
            $this->createSubject([ConfigurationKeyEnum::USE_AUTH => '0'])->isAuthenticationEnabled()
        );
    }

    public function testIsAuthenticationEnabledReturnsDefault(): void
    {
        static::assertTrue(
            $this->createSubject()->isAuthenticationEnabled()
        );
    }

    public function testGetRawWebPathReturnsConfigValue(): void
    {
        $value = 'some-path';

        static::assertSame(
            $value,
            $this->createSubject([ConfigurationKeyEnum::RAW_WEB_PATH => $value])->getRawWebPath()
        );
    }

    public function testGetRawWebPathReturnsDefault(): void
    {
        static::assertSame(
            '',
            $this->createSubject()->getRawWebPath()
        );
    }

    public function testGetWebPathReturnsPath(): void
    {
        $value = 'some-path';

        static::assertSame(
            $value,
            $this->createSubject([ConfigurationKeyEnum::WEB_PATH => $value])->getWebPath()
        );
    }

    public function testGetWebPathReturnsDefault(): void
    {
        static::assertSame(
            '',
            $this->createSubject([])->getWebPath()
        );
    }

    private function createSubject(array $configuration = []): ConfigContainerInterface
    {
        return new ConfigContainer($configuration);
    }
}
