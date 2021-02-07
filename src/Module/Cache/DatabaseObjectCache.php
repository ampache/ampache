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

    private array $adapterList;

    public function __construct(
        ConfigContainerInterface $configContainer,
        array $adapterList
    ) {
        $this->configContainer = $configContainer;
        $this->adapterList     = $adapterList;
    }

    /**
     * This adds the specified object to the specified index in the cache
     *
     * @param string $index
     * @param integer|string $objectId
     * @param array $data
     */
    public function add(string $index, $objectId, array $data): bool
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::MEMORY_CACHE) === false) {
            return false;
        }

        return $this->getAdapter()->add($index, $objectId, $data);
    }

    /**
     * This function clears something from the cache, there are a few places we need to do this
     * in order to have things display correctly
     *
     * @param string $index
     * @param integer $objectId
     */
    public function remove(string $index, $objectId): void
    {
        $this->getAdapter()->remove($index, $objectId);
    }

    public function clear(): void
    {
        $this->getAdapter()->clear();
    }

    /**
     * This checks the cache to see if the specified object is there
     *
     * @param string $index
     * @param string $objectId
     */
    public function exists(string $index, $objectId): bool
    {
        return $this->getAdapter()->exists($index, $objectId);
    }

    /**
     * This attempts to retrieve the specified object from the cache we've got here
     *
     * @param string $index
     * @param integer|string $objectId
     */
    public function retrieve(string $index, $objectId): array
    {
        $adapter = $this->getAdapter();

        if ($adapter->exists($index, $objectId)) {
            $this->hits++;

            return $adapter->retrieve($index, $objectId);
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

            $this->adapter = $this->adapterList[$cacheSetting] ?? new ObjectCacheAdapter\NoopCacheAdapter();
        }

        return $this->adapter;
    }
}
