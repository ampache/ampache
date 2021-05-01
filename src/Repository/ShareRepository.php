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

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\User;

final class ShareRepository implements ShareRepositoryInterface
{
    /**
     * @return int[]
     */
    public function getList(
        User $user
    ): array {
        $sql     = 'SELECT `id` FROM `share`';
        $results = [];

        if (!$user->has_access(AccessLevelEnum::LEVEL_MANAGER)) {
            $sql .= ' WHERE `user` = ?';
            $results[] = $user->getId();
        }

        $db_results = Dba::read($sql, $results);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    public function delete(
        int $shareId,
        User $user
    ): bool {
        $sql    = 'DELETE FROM `share` WHERE `id` = ?';
        $params = [$shareId];
        if (!$user->has_access(AccessLevelEnum::LEVEL_MANAGER)) {
            $sql .= ' AND `user` = ?';
            $params[] = $user->id;
        }

        $result = Dba::write($sql, $params);

        if ($result === false) {
            return false;
        }

        return true;
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        Dba::write(
            'UPDATE `share` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    public function collectGarbage(): void
    {
        Dba::write(
            'DELETE FROM `share` WHERE (`expire_days` > 0 AND (`creation_date` + (`expire_days` * 86400)) < ' . time() . ") OR (`max_counter` > 0 AND `counter` >= `max_counter`)"
        );
    }
}
