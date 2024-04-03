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
 *
 */

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\UpdateInfoEnum;

/**
 * Provides access to the `update_info` table
 */
final readonly class UpdateInfoRepository implements UpdateInfoRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection)
    {
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
