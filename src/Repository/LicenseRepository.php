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
use Ampache\Repository\Model\License;
use Generator;
use PDO;

/**
 * Manages access to license data
 *
 * Tables: `license`
 */
final class LicenseRepository implements LicenseRepositoryInterface
{
    private DatabaseConnectionInterface $connection;

    public function __construct(
        DatabaseConnectionInterface $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Returns a list of licenses accessible by the current user.
     *
     * @return Generator<int, string>
     */
    public function getList(): Generator
    {
        $result = $this->connection->query('SELECT `id`, `name` FROM `license` ORDER BY `name`');

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            yield (int) $row['id'] => (string) $row['name'];
        }
    }

    /**
     * Retrieve a single license-item by its id
     */
    public function findById(int $licenseId): ?License
    {
        $license = new License($licenseId);
        if ($license->isNew()) {
            return null;
        }

        return $license;
    }

    /**
     * This inserts a new license entry, it returns the auto_inc id
     *
     * @return int The id of the created license
     */
    public function create(
        string $name,
        string $description,
        string $externalLink
    ): int {
        $this->connection->query(
            'INSERT INTO `license` (`name`, `description`, `external_link`) VALUES (?, ?, ?)',
            [$name, $description, $externalLink]
        );

        return $this->connection->getLastInsertedId();
    }

    /**
     * This updates a license entry
     */
    public function update(
        License $license,
        string $name,
        string $description,
        string $externalLink
    ): void {
        $this->connection->query(
            'UPDATE `license` SET `name` = ?, `description` = ?, `external_link` = ? WHERE `id` = ?',
            [$name, $description, $externalLink, $license->getId()]
        );
    }

    /**
     * Deletes the license
     */
    public function delete(
        License $license
    ): void {
        $this->connection->query(
            'DELETE FROM `license` WHERE `id` = ?',
            [
                $license->getId()
            ]
        );
    }

    /**
     * Searches for the License by name and external link
     */
    public function find(string $searchValue): ?int
    {
        // lookup the license by name
        $result = $this->connection->fetchOne('SELECT `id` FROM `license` WHERE `name` = ? LIMIT 1');

        if ($result !== false) {
            return (int) $result;
        }

        // lookup the license by external_link
        $result = $this->connection->fetchOne('SELECT `id` FROM `license` WHERE `external_link` = ? LIMIT 1');

        if ($result !== false) {
            return (int) $result;
        }

        return null;
    }
}
