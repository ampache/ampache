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
 *
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Module\System\Dba;

final class AccessRepository implements AccessRepositoryInterface
{
    /**
     * Rreturns a full listing of all access rules on this server
     * @return int[]
     */
    public function getAccessLists(): array
    {
        $sql        = 'SELECT `id` FROM `access_list`';
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Searches for certain ip and config. Returns true if a match was found
     */
    public function findByIp(
        string $userIp,
        int $level,
        string $type,
        ?int $userId
    ): bool {
        $sql = 'SELECT `id` FROM `access_list` ' . 'WHERE `start` <= ? AND `end` >= ? ' . 'AND `level` >= ? AND `type` = ?';

        $params  = array(inet_pton($userIp), inet_pton($userIp), $level, $type);

        if ($userId !== null && $userId != -1) {
            $sql .= " AND `user` IN(?, '-1')";
            $params[] = $userId;
        } else {
            $sql .= " AND `user` = '-1'";
        }

        return Dba::num_rows(Dba::read($sql, $params)) > 0;
    }

    /**
     * deletes the specified access_list entry
     */
    public function delete(int $accessId): void
    {
        Dba::write('DELETE FROM `access_list` WHERE `id` = ?', [$accessId]);
    }

    /**
     * This sees if the ACL that we've specified already exists in order to
     * prevent duplicates. The name is ignored.
     */
    public function exists(
        string $inAddrStart,
        string $inAddrEnd,
        string $type,
        int $userId
    ): bool {
        $db_results = Dba::read(
            'SELECT * FROM `access_list` WHERE `start` = ? AND `end` = ? ' . 'AND `type` = ? AND `user` = ?',
            [$inAddrStart, $inAddrEnd, $type, $userId]
        );

        return Dba::num_rows($db_results) > 0;
    }
}
