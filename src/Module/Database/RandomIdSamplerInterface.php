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

interface RandomIdSamplerInterface
{
    /**
     * Picks up to $limit random ids matching $whereSql, without `ORDER BY RAND()`'s whole-table sort cost.
     * $whereSql matching only a thin slice of the id range can legitimately come back short of $limit.
     *
     * @param list<mixed> $params bound to $whereSql (repeated once per candidate id it probes)
     * @return list<int>
     */
    public function sample(string $table, string $idColumn, string $whereSql, array $params, int $limit): array;
}
