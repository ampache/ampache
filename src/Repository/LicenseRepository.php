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

use Ampache\Module\System\Dba;

final class LicenseRepository implements LicenseRepositoryInterface
{
    /**
     * Returns a list of licenses accessible by the current user.
     *
     * @return int[]
     */
    public function getAll(): array
    {
        $sql = 'SELECT `id` from `license` ORDER BY `name`';

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
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
        Dba::write(
            "INSERT INTO `license` (`name`, `description`, `external_link`) VALUES (?, ?, ?)",
            [$name, $description, $externalLink]
        );

        return (int) Dba::insert_id();
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
        Dba::write(
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
        Dba::write(
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
        $sql        = 'SELECT `id` from `license` WHERE `name` = ?';
        $db_results = Dba::read($sql, array($searchValue));

        while ($row = Dba::fetch_assoc($db_results)) {
            return (int) $row['id'];
        }
        // lookup the license by external_link
        $sql        = 'SELECT `id` from `license` WHERE `external_link` = ?';
        $db_results = Dba::read($sql, array($searchValue));

        while ($row = Dba::fetch_assoc($db_results)) {
            return (int) $row['id'];
        }

        return null;
    }
}
