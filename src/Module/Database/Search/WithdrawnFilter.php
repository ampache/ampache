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

namespace Ampache\Module\Database\Search;

use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;

/**
 * The condition that keeps a withdrawn release out of a listing, in one place.
 *
 * A browse gets it from `Query::_get_filter_sql()` and a smart list from the four search classes, which is
 * four ways into the same rule; written out at each of them, one is eventually missed the way `album_songs`
 * and the artist page were.
 */
final class WithdrawnFilter
{
    /**
     * Adds the condition to a where clause already built, unless the viewer is allowed to see withdrawn items
     */
    public static function apply(string $whereSql, string $type, ?int $userId): string
    {
        if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $userId)) {
            return $whereSql;
        }

        $condition = self::condition($type);

        return ($whereSql !== '' && $whereSql !== '0')
            ? '(' . $whereSql . ') AND ' . $condition
            : $condition;
    }

    /**
     * A disk carries no flag of its own and reads the one on the album it belongs to.
     *
     * `$idColumn` is what the statement already has to correlate on. Omitted, it means the statement already
     * has `album_disk` in scope and reads that row's own `album_id` column; given, it names a column holding
     * a disk id the statement has no other way to reach, e.g. `object_count`.`object_id` on a row counted
     * against `album_disk` -- one hop further than the plain form can make, which is what the join is for.
     */
    public static function condition(string $type, ?string $idColumn = null): string
    {
        if ($type === 'album_disk' && $idColumn !== null) {
            return sprintf(
                'EXISTS (SELECT 1 FROM `album_disk` AS `disk_wd` JOIN `album` AS `album_wd` ON `album_wd`.`id` = `disk_wd`.`album_id` WHERE `disk_wd`.`id` = %s AND `album_wd`.`enabled` = 1)',
                $idColumn
            );
        }

        [$table, $column] = ($type === 'album_disk')
            ? ['album', $idColumn ?? '`album_disk`.`album_id`']
            : [$type, $idColumn];

        if (!in_array($table, ['album', 'artist', 'song'], true)) {
            return '';
        }

        // the subquery always aliases its table: a caller that joins the same one outside would otherwise
        // shadow it, which is why the disk condition was written with an alias in the first place
        return ($column === null)
            ? sprintf('`%s`.`enabled` = 1', $table)
            : sprintf(
                'EXISTS (SELECT 1 FROM `%1$s` AS `%1$s_wd` WHERE `%1$s_wd`.`id` = %2$s AND `%1$s_wd`.`enabled` = 1)',
                $table,
                $column
            );
    }

    /**
     * The same question with the level asked first, for a caller that has a viewer rather than a where clause
     */
    public static function conditionFor(string $type, ?string $idColumn, ?int $userId): string
    {
        return (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $userId))
            ? ''
            : self::condition($type, $idColumn);
    }

    /**
     * `conditionFor()`, folded into a statement already being built with ` AND `, in one call so a future
     * call site cannot copy the lookup without also copying the guard that keeps an empty answer out of it.
     */
    public static function appendCondition(string $sql, string $type, ?string $idColumn, ?int $userId): string
    {
        $condition = self::conditionFor($type, $idColumn, $userId);

        return ($condition === '') ? $sql : $sql . ' AND ' . $condition;
    }
}
