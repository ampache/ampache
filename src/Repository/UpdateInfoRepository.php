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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\UpdateInfoEnum;
use PDO;

/**
 * Provides access to the `update_info` table
 */
final readonly class UpdateInfoRepository implements UpdateInfoRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection) {}

    /**
     * Reads the stored version of every installed plugin, keyed by plugin name
     *
     * @return array<string, int>
     */
    public function getPluginVersions(): array
    {
        // only `Plugin_` keys are ever looked up here, so the rest of update_info stays on the server
        $result = $this->connection->query("SELECT `key`, `value` FROM `update_info` WHERE `key` LIKE 'Plugin\_%';");

        $versions = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $versions[(string) $row['key']] = (int) $row['value'];
        }

        return $versions;
    }

    /**
     * Returns a single value by its key
     *
     * Will return `null` if no item was found
     */
    public function getValueByKey(UpdateInfoEnum $key): ?string
    {
        $value = $this->connection->fetchOne(
            'SELECT value from update_info WHERE `key` = ? LIMIT 1',
            [$key->value]
        );

        if ($value === false) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Drops the stored version of one plugin, which is what marks it uninstalled
     */
    public function removePluginVersion(string $pluginName): void
    {
        $this->connection->query(
            'DELETE FROM `update_info` WHERE `key` = ?',
            ['Plugin_' . $pluginName]
        );
    }

    /**
     * Stores the version of an installed plugin
     */
    public function setPluginVersion(string $pluginName, int $version): void
    {
        $this->connection->query(
            'REPLACE INTO `update_info` SET `key` = ?, `value` = ?',
            ['Plugin_' . $pluginName, $version]
        );
    }

    /**
     * Sets a value using the provided params
     */
    public function setValue(UpdateInfoEnum $key, string $value): void
    {
        $result = $this->connection->query(
            'UPDATE `update_info` SET `value` = ? WHERE `key` = ?',
            [$value, $key->value]
        );

        if ($result->rowCount() === 0) {
            $this->connection->query(
                'INSERT INTO `update_info` (`key`, `value`) VALUES (?, ?)',
                [$key->value, $value]
            );
        }
    }
}
