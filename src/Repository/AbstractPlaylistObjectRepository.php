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

namespace Ampache\Repository;

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\playlist_object;

/**
 * The writes `playlist` and `search` share
 *
 * Tables: `playlist` / `search`, and `user_playlist_map`
 */
abstract readonly class AbstractPlaylistObjectRepository implements PlaylistObjectRepositoryInterface
{
    public function __construct(
        protected DatabaseConnectionInterface $connection,
    ) {}

    /**
     * Drops every collaborator of the list, for use when the list itself is deleted
     *
     * Nothing else clears this table, so skipping it strands rows that a later list inherits if it is
     * ever given the freed id — restoring a dump is enough to reset AUTO_INCREMENT and do that.
     */
    public function deleteCollaborators(playlist_object $item): void
    {
        $this->connection->query(
            'DELETE FROM `user_playlist_map` WHERE `playlist_id` = ?;',
            [$this->collaborateKey($item)]
        );
    }

    /**
     * Stores the cached item count
     */
    public function setLastCount(playlist_object $item, int $count): void
    {
        $this->setCachedTotal($item, 'last_count', $count);
    }

    /**
     * Stores the cached total duration
     */
    public function setLastDuration(playlist_object $item, int $duration): void
    {
        $this->setCachedTotal($item, 'last_duration', $duration);
    }

    /**
     * Replaces the set of users allowed to collaborate on the list
     *
     * @param int[] $userIds
     */
    public function updateCollaborators(playlist_object $item, array $userIds): void
    {
        $mapKey = $this->collaborateKey($item);

        $collaborate = implode(',', $userIds);
        $sql         = ($collaborate === '')
            ? 'DELETE FROM `user_playlist_map` WHERE `playlist_id` = ?;'
            : 'DELETE FROM `user_playlist_map` WHERE `playlist_id` = ? AND `user_id` NOT IN (' . $collaborate . ');';

        $this->connection->query($sql, [$mapKey]);

        foreach ($userIds as $userId) {
            $this->connection->query(
                'INSERT IGNORE INTO `user_playlist_map` (`playlist_id`, `user_id`) VALUES (?, ?);',
                [$mapKey, $userId]
            );
        }
    }

    /**
     * The value `user_playlist_map` stores for this item. A smartlist is keyed by its prefixed id so both list kinds can share the one table.
     */
    abstract protected function collaborateKey(playlist_object $item): int|string;

    /**
     * The table the shared columns live in
     */
    abstract protected function tableName(): string;

    /**
     * The column is whitelisted by the two callers above rather than escaped, because it is interpolated into the statement.
     */
    private function setCachedTotal(playlist_object $item, string $column, int $value): void
    {
        if ($item->getId() === 0 || $value < 0) {
            return;
        }

        $this->connection->query(
            sprintf('UPDATE `%s` SET `%s` = ? WHERE `id` = ?', $this->tableName(), $column),
            [$value, $item->getId()]
        );
    }
}
