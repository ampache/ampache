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

namespace Ampache\Module\Api\Method;

use Ampache\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ObjectTypeGateTest extends MockeryTestCase
{
    /**
     * @return array<string, array{0: int}>
     */
    public static function olderApiVersionProvider(): array
    {
        return [
            'api5' => [5],
            'api6' => [6],
        ];
    }

    public function testIndexTypes8AddsAlbumDisk(): void
    {
        $this->assertContains('album_disk', ObjectTypeGate::INDEX_TYPES_8);
    }

    public function testIndexTypes8IsASupersetOfIndexTypes(): void
    {
        $this->assertSame([], array_diff(ObjectTypeGate::INDEX_TYPES, ObjectTypeGate::INDEX_TYPES_8));
    }

    /**
     * `INDEX_TYPES` is also read directly by `GetIndexes6Method`, which has no version to branch on,
     * so `album_disk` must never leak into it
     */
    public function testIndexTypesHasNoVersion8Types(): void
    {
        $this->assertNotContains('album_disk', ObjectTypeGate::INDEX_TYPES);
    }

    public function testIndexTypesReturnsTheExtendedListForApi8(): void
    {
        $this->assertSame(ObjectTypeGate::INDEX_TYPES_8, ObjectTypeGate::indexTypes(8));
    }

    #[DataProvider(methodName: 'olderApiVersionProvider')]
    public function testIndexTypesReturnsTheSharedListForOlderVersions(int $apiVersion): void
    {
        $this->assertSame(ObjectTypeGate::INDEX_TYPES, ObjectTypeGate::indexTypes($apiVersion));
    }
}
