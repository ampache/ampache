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
use Ampache\Repository\Model\Label;
use DateTimeInterface;
use PDO;

final class LabelRepository implements LabelRepositoryInterface
{
    private DatabaseConnectionInterface $connection;

    public function __construct(
        DatabaseConnectionInterface $connection
    ) {
        $this->connection = $connection;
    }

    public function findById(int $labelId): ?Label
    {
        $label = new Label($labelId);
        if ($label->isNew()) {
            return null;
        }

        return $label;
    }

    /**
     * @return array<int, string>
     */
    public function getByArtist(int $artistId): array
    {
        $labels = [];

        $result = $this->connection->query(
            'SELECT `label`.`id`, `label`.`name` FROM `label` LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id` WHERE `label_asso`.`artist` = ?',
            [$artistId]
        );

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $labels[(int) $row['id']] = $row['name'];
        }

        return $labels;
    }

    /**
     * Return the list of all available labels
     *
     * @return array<int, string>
     */
    public function getAll(): array
    {
        $result = $this->connection->query('SELECT `id`, `name` FROM `label`');

        $labels = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $labels[(int) $row['id']] = $row['name'];
        }

        return $labels;
    }

    public function lookup(string $labelName, int $labelId = 0): int
    {
        $ret  = -1;
        $name = trim($labelName);

        if ($name !== '') {
            $ret    = 0;
            $sql    = 'SELECT `id` FROM `label` WHERE `name` = ?';
            $params = [$name];
            if ($labelId > 0) {
                $sql .= ' AND `id` != ?';
                $params[] = $labelId;
            }

            $result = $this->connection->fetchOne($sql, $params);

            if ($result !== false) {
                $ret = (int) $result;
            }
        }

        return $ret;
    }

    public function removeArtistAssoc(int $labelId, int $artistId): void
    {
        $this->connection->query(
            'DELETE FROM `label_asso` WHERE `label` = ? AND `artist` = ?',
            [$labelId, $artistId]
        );
    }

    public function addArtistAssoc(int $labelId, int $artistId, DateTimeInterface $date): void
    {
        $this->connection->query(
            'INSERT INTO `label_asso` (`label`, `artist`, `creation_date`) VALUES (?, ?, ?)',
            [$labelId, $artistId, $date->getTimestamp()]
        );
    }

    public function delete(int $labelId): void
    {
        $this->connection->query(
            'DELETE FROM `label` WHERE `id` = ?',
            [$labelId]
        );
    }

    /**
     * This cleans out unused labels
     */
    public function collectGarbage(): void
    {
        $this->connection->query('DELETE FROM `label_asso` WHERE `label_asso`.`artist` NOT IN (SELECT `artist`.`id` FROM `artist`)');
        $this->connection->query('DELETE FROM `label` WHERE `id` NOT IN (SELECT `label` FROM `label_asso`) AND `user` IS NULL');
    }
}
