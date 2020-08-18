<?php

declare(strict_types=1);

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

namespace Config;

use Ampache\Config\ConfigContainer;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ConfigContainerTest extends MockeryTestCase
{
    public function testGetReturnsValue(): void
    {
        $key   = 'some-key';
        $value = 'some-value';

        $subject = $this->createSubject([$key => $value]);

        $this->assertSame(
            $value,
            $subject->get($key)
        );
    }

    public function testGetReturnsNullIfKeyNotSet(): void
    {
        $this->assertNull(
            $this->createSubject([])->get('foobar')
        );
    }

    public function testGetSessionNameReturnsValue(): void
    {
        $value = 'some-value';

        $subject = $this->createSubject([
            ConfigurationKeyEnum::SESSION_NAME => $value
        ]);

        $this->assertSame(
            $value,
            $subject->getSessionName()
        );
    }

    public function testGetSessionNameReturnsEmptyStringIfNotSet(): void
    {
        $this->assertSame(
            '',
            $this->createSubject([])->getSessionName()
        );
    }

    private function createSubject(array $configuration): ConfigContainerInterface
    {
        return new ConfigContainer($configuration);
    }
}
