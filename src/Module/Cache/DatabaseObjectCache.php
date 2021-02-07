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

namespace Ampache\Module\Cache;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;

final class DatabaseObjectCache implements DatabaseObjectCacheInterface
{
    private ConfigContainerInterface $configContainer;

    private ?ObjectCacheAdapter\ObjectCacheAdapterInterface $adapter = null;

    private int $hits = 0;

    private const ADAPTER_LIST = [
        '0' => ObjectCacheAdapter\NoopCacheAdapter::class,
        '1' => ObjectCacheAdapter\SimpleArrayCacheAdapter::class,
        'simple' => ObjectCacheAdapter\SimpleArrayCacheAdapter::class,
    ];

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * This adds the specified object to the specified index in the cache
     *
     * @param string $index
     * @param integer|string $object_id
     * @param array $data
     */
    public function add(string $index, $object_id, array $data): bool
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::MEMORY_CACHE) === false) {
            return false;
        }

        return $this->getAdapter()->add($index, $object_id, $data);
    }

    /**
     * This function clears something from the cache, there are a few places we need to do this
     * in order to have things display correctly
     * @param string $index
     * @param integer $object_id
     */
    public function remove(string $index, $object_id): void
    {
        $this->getAdapter()->remove($index, $object_id);
    }

    public function clear()
    {
        $this->getAdapter()->clear();
    }

    /**
     * this checks the cache to see if the specified object is there
     *
     * @param string $index
     * @param string $object_id
     */
    public function exists(string $index, $object_id): bool
    {
        return $this->getAdapter()->exists($index, $object_id);
    }

    /**
     * This attempts to retrieve the specified object from the cache we've got here
     *
     * @param string $index
     * @param integer|string $object_id
     */
    public function retrieve(string $index, $object_id): array
    {
        $adapter = $this->getAdapter();

        if ($adapter->exists($index, $object_id)) {
            $this->hits++;

            return $adapter->retrieve($index, $object_id);
        }

        return [];
    }

    public function getCacheHitAmount(): int
    {
        return $this->hits;
    }

    private function getAdapter(): ObjectCacheAdapter\ObjectCacheAdapterInterface
    {
        if ($this->adapter === null) {
            $cacheSetting = (string) $this->configContainer->get(ConfigurationKeyEnum::MEMORY_CACHE_ADAPTER);

            $adapterClass  = static::ADAPTER_LIST[$cacheSetting] ?? ObjectCacheAdapter\NoopCacheAdapter::class;

            $this->adapter = new $adapterClass();
        }

        return $this->adapter;
    }
}
