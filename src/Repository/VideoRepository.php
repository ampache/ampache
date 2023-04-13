<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

use Ampache\Repository\Model\Catalog;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;

final class VideoRepository implements VideoRepositoryInterface
{
    /**
     * This returns a number of random videos.
     *
     * @return int[]
     */
    public function getRandom(
        int $userId,
        ?int $count = 1
    ): array {
        $results    = [];
        $sql        = "SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` WHERE `video`.`enabled` = '1' AND `catalog`.`id` IN (" . implode(',', Catalog::get_catalogs('', $userId)) . ") ORDER BY RAND() LIMIT " . (string) ($count);
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Return the number of entries in the database...
     *
     * @param string $type
     *
     * @return int
     */
    public function getItemCount(string $type): int
    {
        $type = ObjectTypeToClassNameMapper::VIDEO_TYPES[$type];

        $sql        = 'SELECT COUNT(*) AS `count` FROM `' . strtolower((string) $type) . '`;';
        $db_results = Dba::read($sql);
        if ($results = Dba::fetch_assoc($db_results)) {
            if (array_key_exists('count', $results)) {
                return (int) $results['count'];
            }
        }

        return 0;
    } // get_item_count
}
