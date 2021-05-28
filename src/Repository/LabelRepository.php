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

use Doctrine\DBAL\Connection;

final class LabelRepository implements LabelRepositoryInterface
{
    private Connection $database;

    public function __construct(
        Connection $connection
    ) {
        $this->database = $connection;
    }

    /**
     * @return array<int, string>
     */
    public function getByArtist(int $artistId): array
    {
        $dbResults = $this->database->executeQuery(
            'SELECT `label`.`id`, `label`.`name` FROM `label` LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id` WHERE `label_asso`.`artist` = ?',
            [$artistId]
        );

        $results = [];

        while ($row = $dbResults->fetchAssociative()) {
            $results[(int) $row['id']] = $row['name'];
        }

        return $results;
    }

    /**
     * Return the list of all available labels
     *
     * @return array<int, string>
     */
    public function getAll(): array
    {
        $db_results = $this->database->executeQuery('SELECT `id`, `name` FROM `label`');

        $results = [];

        while ($row = $db_results->fetchAssociative()) {
            $results[(int) $row['id']] = $row['name'];
        }

        return $results;
    }

    public function lookup(string $labelName, int $labelId = 0): int
    {
        $ret  = -1;
        $name = trim($labelName);
        if (!empty($name)) {
            $ret    = 0;
            $sql    = 'SELECT `id` FROM `label` WHERE `name` = ?';
            $params = [$name];
            if ($labelId > 0) {
                $sql .= ' AND `id` != ?';
                $params[] = $labelId;
            }
            $dbResult = $this->database->fetchOne($sql, $params);
            if ($dbResult !== false) {
                $ret = (int) $dbResult;
            }
        }

        return $ret;
    }

    public function removeArtistAssoc(int $labelId, int $artistId): void
    {
        $this->database->executeQuery(
            'DELETE FROM `label_asso` WHERE `label` = ? AND `artist` = ?',
            [$labelId, $artistId]
        );
    }

    public function addArtistAssoc(int $labelId, int $artistId): void
    {
        $this->database->executeQuery(
            'INSERT INTO `label_asso` (`label`, `artist`, `creation_date`) VALUES (?, ?, ?)',
            [$labelId, $artistId, time()]
        );
    }

    public function delete(int $labelId): bool
    {
        $result = $this->database->executeQuery(
            'DELETE FROM `label` WHERE `id` = ?',
            [$labelId]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Returns a list of artist ids associated with the given label
     *
     * @return int[]
     */
    public function getArtists(int $labelId): array
    {
        $dbResults = $this->database->executeQuery(
            'SELECT `artist` FROM `label_asso` WHERE `label` = ?',
            [$labelId]
        );

        $results = [];

        while ($artistId = $dbResults->fetchOne()) {
            $results[] = (int) $artistId;
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDataById(int $labelId): array
    {
        $result = $this->database->fetchAssociative(
            'SELECT * FROM `label` WHERE `id`= ?',
            [$labelId]
        );

        if ($result === false) {
            return [];
        }

        return $result;
    }

    public function update(
        int $labelId,
        string $name,
        string $category,
        string $summary,
        string $address,
        string $email,
        string $website,
        string $country,
        string $musicBrainzId,
        int $active
    ): void {
        $this->database->executeQuery(
            'UPDATE `label` SET `name` = ?, `category` = ?, `summary` = ?, `address` = ?, `email` = ?, `website` = ?, `country` = ?, `mbid` = ?, `active` = ? WHERE `id` = ?',
            [$name, $category, $summary, $address, $email, $website, $labelId]
        );
    }
}
