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
     * `$idColumn` is what the statement already has to correlate on, and null says it reads the table holding
     * the flag itself, which is the difference between naming the column and going through a subquery.
     */
    public static function condition(string $type, ?string $idColumn = null): string
    {
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
}
