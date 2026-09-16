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

namespace Ampache\Module\Database;

final readonly class RandomIdSampler implements RandomIdSamplerInterface
{
    // total probe budget is $limit times this; a probe landing on a gap or duplicate finds nothing new
    private const int MAX_ATTEMPTS_PER_ID = 10;

    public function __construct(private DatabaseConnectionInterface $connection) {}

    public function sample(string $table, string $idColumn, string $whereSql, array $params, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $range = $this->connection->fetchRow(
            sprintf('SELECT MIN(`%s`) AS `min_id`, MAX(`%s`) AS `max_id` FROM `%s` %s', $idColumn, $idColumn, $table, $whereSql),
            $params
        );
        if ($range === false || $range['min_id'] === null || $range['max_id'] === null) {
            return [];
        }

        $min = (int) $range['min_id'];
        $max = (int) $range['max_id'];

        $probeSql = sprintf('SELECT `%s` FROM `%s` %s AND `%s` >= ? ORDER BY `%s` LIMIT 1', $idColumn, $table, $whereSql, $idColumn, $idColumn);

        $ids      = [];
        $attempts = 0;
        while (count($ids) < $limit && $attempts < $limit * self::MAX_ATTEMPTS_PER_ID) {
            $attempts++;
            $id = $this->connection->fetchOne($probeSql, [...$params, random_int($min, $max)]);
            if ($id !== false) {
                $ids[(int) $id] = (int) $id;
            }
        }

        return array_values($ids);
    }
}
