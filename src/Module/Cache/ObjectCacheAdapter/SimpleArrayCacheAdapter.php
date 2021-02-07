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

final class SimpleArrayCacheAdapter implements ObjectCacheAdapterInterface
{
    private array $cache = [];

    /**
     * This adds the specified object to the specified index in the cache
     *
     * @param string $index
     * @param integer|string $objectId
     * @param array $data
     */
    public function add(string $index, $objectId, array $data): bool
    {
        $this->cache[$index][$objectId] = $data;

        return true;
    }

    /**
     * This function clears something from the cache, there are a few places we need to do this
     * in order to have things display correctly
     * @param string $index
     * @param integer $objectId
     */
    public function remove(string $index, $objectId): void
    {
        if (isset($this->cache[$index]) && isset($this->cache[$index][$objectId])) {
            unset($this->cache[$index][$objectId]);
        }
    }

    public function clear(): void
    {
        $this->cache = [];
    }

    /**
     * this checks the cache to see if the specified object is there
     *
     * @param string $index
     * @param string $objectId
     */
    public function exists(string $index, $objectId): bool
    {
        // Make sure we've got some parents here before we dive below
        if (!isset($this->cache[$index])) {
            return false;
        }

        return isset($this->cache[$index][$objectId]);
    }

    /**
     * This attempts to retrieve the specified object from the cache we've got here
     *
     * @param string $index
     * @param integer|string $objectId
     */
    public function retrieve(string $index, $objectId): array
    {
        // Check if the object is set
        if (isset($this->cache[$index]) && isset($this->cache[$index][$objectId])) {
            return $this->cache[$index][$objectId];
        }

        return [];
    }
}
