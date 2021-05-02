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
use Ampache\Repository\Model\ShareInterface;
use Ampache\Repository\Model\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class ShareRepository implements ShareRepositoryInterface
{
    private Connection $database;

    public function __construct(
        Connection $database
    ) {
        $this->database = $database;
    }

    /**
     * @return int[]
     */
    public function getList(
        User $user
    ): array {
        $sql    = 'SELECT `id` FROM `share`';
        $params = [];

        if (!$user->has_access(AccessLevelEnum::LEVEL_MANAGER)) {
            $sql .= ' WHERE `user` = ?';
            $params[] = $user->getId();
        }

        $dbResults = $this->database->executeQuery(
            $sql,
            $params
        );

        $results = [];
        while ($rowId = $dbResults->fetchOne()) {
            $results[] = (int) $rowId;
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
            $params[] = $user->getId();
        }

        try {
            $this->database->executeQuery($sql, $params);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->database->executeQuery(
            'UPDATE `share` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    public function collectGarbage(): void
    {
        $this->database->executeQuery(
            'DELETE FROM `share` WHERE (`expire_days` > 0 AND (`creation_date` + (`expire_days` * 86400)) < ?) OR (`max_counter` > 0 AND `counter` >= `max_counter`)',
            [time()]
        );
    }

    public function saveAccess(
        ShareInterface $share,
        int $lastVisitDate
    ): void {
        $this->database->executeQuery(
            'UPDATE `share` SET `counter` = (`counter` + 1), lastvisit_date = ? WHERE `id` = ?',
            [$lastVisitDate, $share->getId()]
        );
    }

    public function update(
        ShareInterface $share,
        int $maxCounter,
        int $expire,
        int $allowStream,
        int $allowDownload,
        string $description,
        ?int $userId
    ): void {
        $sql    = 'UPDATE `share` SET `max_counter` = ?, `expire_days` = ?, `allow_stream` = ?, `allow_download` = ?, `description` = ? WHERE `id` = ?';
        $params = [
            $maxCounter,
            $expire,
            $allowStream,
            $allowDownload,
            $description,
            $share->getId()
        ];
        if ($userId) {
            $sql .= ' AND `user` = ?';
            $params[] = $userId;
        }

        $this->database->executeQuery(
            $sql,
            $params
        );
    }
}
