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

use Ampache\Module\System\Dba;

final class BookmarkRepository implements BookmarkRepositoryInterface
{
    /**
     * @return int[]
     */
    public function getBookmarks(int $userId): array
    {
        $ids = [];

        $sql        = "SELECT `id` FROM `bookmark` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($userId));
        while ($results = Dba::fetch_assoc($db_results)) {
            $ids[] = (int) $results['id'];
        }

        return $ids;
    }

    public function delete(int $bookmarkId): bool
    {
        $sql = "DELETE FROM `bookmark` WHERE `id` = ?";

        return Dba::write($sql, array($bookmarkId)) !== false;
    }

    /**
     * Remove bookmark for items that no longer exist.
     */
    public function collectGarbage(): void
    {
        $types = ['song', 'video', 'podcast_episode'];
        foreach ($types as $type) {
            Dba::write("DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `$type` ON `$type`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = '$type' AND `$type`.`id` IS NULL;");
        }
    }

    public function update(int $userId, int $position, int $date): void
    {
        Dba::write(
            'UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `id` = ?',
            [$position, $date, $userId]
        );
    }
}
