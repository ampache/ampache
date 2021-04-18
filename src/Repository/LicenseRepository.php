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

namespace Ampache\Repository;

use Ampache\Repository\Model\LicenseInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Doctrine\DBAL\Connection;

final class LicenseRepository implements LicenseRepositoryInterface
{
    private Connection $connection;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        Connection $connection,
        ModelFactoryInterface $modelFactory
    ) {
        $this->connection   = $connection;
        $this->modelFactory = $modelFactory;
    }

    /**
     * Returns a list of licenses accessible by the current user.
     *
     * @return LicenseInterface[]
     */
    public function getAll(): array
    {
        $dbResults = $this->connection->executeQuery('SELECT `id` from `license` ORDER BY `name`');

        $results = [];
        while ($rowId = $dbResults->fetchOne()) {
            $results[] = $this->modelFactory->createLicense((int) $rowId);
        }

        return $results;
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
        $this->connection->executeQuery(
            'INSERT INTO `license` (`name`, `description`, `external_link`) VALUES (? , ?, ?)',
            [$name, $description, $externalLink]
        );

        return (int) $this->connection->lastInsertId();
    }

    /**
     * This updates a license entry
     */
    public function update(
        int $licenseId,
        string $name,
        string $description,
        string $externalLink
    ): void {
        $this->connection->executeQuery(
            'UPDATE `license` SET `name` = ?, `description` = ?, `external_link` = ? WHERE `id` = ?',
            [$name, $description, $externalLink, $licenseId]
        );
    }

    /**
     * Deletes the license
     */
    public function delete(
        int $licenseId
    ): void {
        $this->connection->executeQuery(
            'DELETE FROM `license` WHERE `id` = ?',
            [$licenseId]
        );
    }

    /**
     * Searches for the License by name and external link
     */
    public function find(string $searchValue): ?int
    {
        // lookup the license by name
        $licenseId = $this->connection->fetchOne(
            'SELECT `id` from `license` WHERE `name` = ?',
            [$searchValue]
        );
        if ($licenseId === false) {
            // lookup the license by external_link
            $licenseId = $this->connection->fetchOne(
                'SELECT `id` from `license` WHERE `external_link` = ?',
                [$searchValue]
            );
        }

        if ($licenseId === false) {
            return null;
        }

        return (int) $licenseId;
    }

    /**
     * Fetches the data for a certain entry
     *
     * @return array{"id": int, "name": string|null, "description": string|null, "external_link": ?string}
     */
    public function getDataById(int $licenseId): array
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM `license` WHERE `id` = ?',
            [$licenseId]
        );

        return $data ?: [];
    }
}
