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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Clip;
use Ampache\Repository\Model\Movie;
use Ampache\Repository\Model\Personal_Video;
use Ampache\Repository\Model\TVShow_Episode;
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
        $results = [];

        if (!$count) {
            $count = 1;
        }

        $sql   = "SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` ";
        $where = "WHERE `video`.`enabled` = '1' ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` ";
            $where .= "AND `catalog`.`enabled` = '1' ";
        }

        $sql .= $where;
        if (AmpConfig::get('catalog_filter') && $userId !== null) {
            $sql .= " AND" . Catalog::get_user_filter('video', $userId);
        }
        $sql .= "ORDER BY RAND() LIMIT " . (string) ($count);
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
        $db_results = Dba::read($sql,array());
        if ($results = Dba::fetch_assoc($db_results)) {
            if ($results['count']) {
                return (int) $results['count'];
            }
        }

        return 0;
    } // get_item_count
}
