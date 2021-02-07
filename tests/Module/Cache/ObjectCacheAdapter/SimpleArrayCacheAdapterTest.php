<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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
 */

declare(strict_types=1);

namespace Ampache\Module\Cache\ObjectCacheAdapter;

use Ampache\MockeryTestCase;

class SimpleArrayCacheAdapterTest extends MockeryTestCase
{
    private ?SimpleArrayCacheAdapter $subject;

    public function setUp(): void
    {
        $this->subject = new SimpleArrayCacheAdapter();
    }

    public function testAddAddsToCacheAndReturnsTrue(): void
    {
        $index    = 'some-index';
        $objectId = 'some-object-id';
        $data     = ['some-data'];

        $this->assertFalse(
            $this->subject->exists($index, $objectId)
        );
        $this->assertSame(
            [],
            $this->subject->retrieve($index, $objectId)
        );

        $this->subject->add($index, $objectId, $data);

        $this->assertTrue(
            $this->subject->exists($index, $objectId)
        );

        $this->assertSame(
            $data,
            $this->subject->retrieve($index, $objectId)
        );
        $this->subject->clear();
        $this->assertFalse(
            $this->subject->exists($index, $objectId)
        );
    }

    public function testRemoveRemovesExistingEntry(): void
    {
        $index    = 'some-index';
        $objectId = 'some-object-id';
        $data     = ['some-data'];

        $this->subject->add($index, $objectId, $data);

        $this->assertTrue(
            $this->subject->exists($index, $objectId)
        );

        $this->subject->remove($index, $objectId);

        $this->assertFalse(
            $this->subject->exists($index, $objectId)
        );
    }
}
