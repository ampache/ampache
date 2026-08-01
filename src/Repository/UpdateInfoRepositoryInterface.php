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

namespace Ampache\Repository;

use Ampache\Repository\Model\UpdateInfoEnum;

interface UpdateInfoRepositoryInterface
{
    /**
     * Reads every cached total, keyed by the table or metric it counts
     *
     * @return array<string, int>
     */
    public function getAllCounts(): array;

    /**
     * Reads one cached total, or 0 when nothing has stored it yet
     */
    public function getCountByKey(string $key): int;

    /**
     * Reads the stored version of every installed plugin, keyed by plugin name
     *
     * @return array<string, int>
     */
    public function getPluginVersions(): array;

    /**
     * Returns a single value by its key
     *
     * Will return `null` if no item was found
     */
    public function getValueByKey(UpdateInfoEnum $key): ?string;

    /**
     * Drops the stored version of one plugin, which is what marks it uninstalled
     */
    public function removePluginVersion(string $pluginName): void;

    /**
     * Stores one cached total, replacing whatever was there
     */
    public function setCountByKey(string $key, float|int $value): void;

    /**
     * Stores the version of an installed plugin
     */
    public function setPluginVersion(string $pluginName, int $version): void;

    /**
     * Sets a value using the provided params
     */
    public function setValue(UpdateInfoEnum $key, string $value): void;
}
