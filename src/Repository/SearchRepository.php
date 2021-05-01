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

use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Dba;

final class SearchRepository implements SearchRepositoryInterface
{
    /**
     * Returns a list of playlists accessible by the user.
     *
     * @param null|int $userId
     * @param string $playlistName
     * @param bool $like
     *
     * @return int[]
     */
    public function getSmartlists(
        ?int $userId = -1,
        string $playlistName = '',
        bool $like = true
    ): array {
        $is_admin = (Access::check('interface', 100, $userId) || $userId == -1);
        $sql      = 'SELECT CONCAT(\'smart_\', `id`) AS `id` FROM `search`';
        $params   = array();

        if (!$is_admin) {
            $sql .= 'WHERE (`user` = ? OR `type` = \'public\') ';
            $params[] = $userId;
        }
        if ($playlistName !== '') {
            $playlistName = (!$like) ? "= '" . $playlistName . "'" : "LIKE  '%" . $playlistName . "%' ";
            if ($is_admin) {
                $sql .= "AND `name` " . $playlistName;
            }
        }
        $sql .= 'ORDER BY `name`';
        //debug_event(self::class, 'get_smartlists ' . $sql, 5);

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }
}
