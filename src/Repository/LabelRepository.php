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

final class LabelRepository implements LabelRepositoryInterface
{
    /**
     * @return array<int, string>
     */
    public function getByArtist(int $artistId): array
    {
        $results = [];

        $db_results = Dba::read(
            "SELECT `label`.`id`, `label`.`name` FROM `label` LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id` WHERE `label_asso`.`artist` = ?",
            [$artistId]
        );

        while ($row = Dba::fetch_assoc($db_results)) {
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
        $db_results = Dba::read('SELECT `id`, `name` FROM `label`');
        $results    = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[(int) $row['id']] = $row['name'];
        }

        return $results;
    }

    public function lookup(string $labelName, int $label_id = 0): int
    {
        $ret  = -1;
        $name = trim($labelName);
        if (!empty($name)) {
            $ret    = 0;
            $sql    = "SELECT `id` FROM `label` WHERE `name` = ?";
            $params = [$name];
            if ($label_id > 0) {
                $sql .= " AND `id` != ?";
                $params[] = $label_id;
            }
            $db_results = Dba::read($sql, $params);
            if ($row = Dba::fetch_assoc($db_results)) {
                $ret = (int) $row['id'];
            }
        }

        return $ret;
    }

    public function removeArtistAssoc(int $labelId, int $artistId): void
    {
        Dba::write(
            'DELETE FROM `label_asso` WHERE `label` = ? AND `artist` = ?',
            [$labelId, $artistId]
        );
    }

    public function addArtistAssoc(int $labelId, int $artistId): void
    {
        Dba::write(
            'INSERT INTO `label_asso` (`label`, `artist`, `creation_date`) VALUES (?, ?, ?)',
            [$labelId, $artistId, time()]
        );
    }

    public function delete(int $labelId): bool
    {
        $result = Dba::write(
            "DELETE FROM `label` WHERE `id` = ?",
            [$labelId]
        );

        return $result !== false;
    }
}
