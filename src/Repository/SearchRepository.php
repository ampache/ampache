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

final class SearchRepository implements SearchRepositoryInterface
{
    /**
     * Returns a list of playlists accessible by the user.
     *
     * @param bool $includePublic
     * @param int $userId
     * @param string $playlistName
     * @param bool $like
     *
     * @return int[]
     */
    public function getSmartlists(
        bool $includePublic = true,
        int $userId = -1,
        string $playlistName = '',
        bool $like = true
    ): array {
        // Search for smartplaylists
        $sql    = "SELECT CONCAT('smart_', `id`) AS `id` FROM `search`";
        $params = [];

        if ($userId > -1 && $includePublic) {
            $sql .= " WHERE (`user` = ? OR `type` = 'public')";
            $params[] = $userId;
        }
        if ($userId > -1 && !$includePublic) {
            $sql .= ' WHERE `user` = ?';
            $params[] = $userId;
        }
        if (!$userId > -1 && $includePublic) {
            $sql .= " WHERE `type` = 'public'";
        }

        if ($playlistName !== '') {
            $playlistName = (!$like) ? "= '" . $playlistName . "'" : "LIKE  '%" . $playlistName . "%' ";
            if (count($params) > 0 || $includePublic) {
                $sql .= " AND `name` " . $playlistName;
            } else {
                $sql .= " WHERE `name` " . $playlistName;
            }
        }
        $sql .= ' ORDER BY `name`';

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }
}
