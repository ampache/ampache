<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
 */

namespace Ampache\Module\System\Cache;

use PHPUnit\Framework\TestCase;

class ArrayCacheDriverTest extends TestCase
{
    private ArrayCacheDriver $subject;

    protected function setUp(): void
    {
        $this->subject = new ArrayCacheDriver();
    }

    public function testGetReturnsDefaultIfNotSet(): void
    {
        $default = 'snafu';

        static::assertSame(
            $default,
            $this->subject->get('booh', $default)
        );
    }

    public function testGetReturnsSetValue(): void
    {
        $value = 'some-value';
        $key   = 'some-key';

        $this->subject->set($key, $value);

        static::assertTrue(
            $this->subject->has($key)
        );

        static::assertSame(
            $value,
            $this->subject->get($key)
        );
    }

    public function testGetReturnsDefaultAfterClear(): void
    {
        $value = 'some-value';
        $key   = 'some-key';

        $this->subject->set($key, $value);

        $this->subject->clear();

        static::assertFalse(
            $this->subject->has($key)
        );

        static::assertNull(
            $this->subject->get($key)
        );
    }

    public function testGetReturnsDefaultAfterDeletion(): void
    {
        $value = 'some-value';
        $key   = 'some-key';

        $this->subject->set($key, $value);

        $this->subject->delete($key);

        static::assertNull(
            $this->subject->get($key)
        );
    }

    public function testGetMultipleReturnsSetData(): void
    {
        $key1 = 'snafu';
        $key2 = 'foobar';

        $value1 = 'foo';
        $value2 = 'baz';

        $this->subject->setMultiple([$key1 => $value1, $key2 => $value2]);

        static::assertSame(
            [$key1 => $value1, $key2 => $value2],
            iterator_to_array($this->subject->getMultiple([$key1, $key2]))
        );
    }

    public function testGetMultipleReturnsPartialDataAfterDeletion(): void
    {
        $key1 = 'snafu';
        $key2 = 'foobar';

        $value1 = 'foo';
        $value2 = 'baz';

        $this->subject->setMultiple([$key1 => $value1, $key2 => $value2]);
        $this->subject->deleteMultiple([$key1]);

        static::assertSame(
            [$key1 => null, $key2 => $value2],
            iterator_to_array($this->subject->getMultiple([$key1, $key2]))
        );
    }
}
