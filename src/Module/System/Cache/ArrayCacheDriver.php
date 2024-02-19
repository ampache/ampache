<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Generator;
use Psr\SimpleCache\CacheInterface;

/**
 * Simple array-cache implementation
 *
 * @see CacheInterface
 */
final class ArrayCacheDriver implements CacheInterface
{
    /** @var array<string, scalar> */
    private array $cache = [];

    public function get($key, $default = null): mixed
    {
        return $this->cache[$key] ?? $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->cache[$key] = $value;

        return true;
    }

    public function delete($key): bool
    {
        if ($this->has($key)) {
            unset($this->cache[$key]);
        }

        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];

        return true;
    }

    /**
     * @param list<string> $keys
     *
     * @return Generator<string, scalar>
     */
    public function getMultiple($keys, $default = null): Generator
    {
        foreach ($keys as $key) {
            yield $key => $this->cache[$key] ?? $default;
        }
    }

    /**
     * @param iterable<string, scalar> $values
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->cache[$key] = $value;
        }

        return true;
    }

    /**
     * @param list<string> $keys
     */
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->cache);
    }
}
