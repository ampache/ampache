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

use Ampache\Module\Catalog\CatalogCounterInterface;
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
        protected CatalogCounterInterface $catalogCounter,
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
     * Writes the fields the edit form owns, in one statement
     *
     * Deliberately not every column: `date` is the creation time, and `last_update`, `last_count`,
     * `last_duration` and `collaborate` each have their own writer above.
     */
    public function persist(playlist_object $item): void
    {
        $columns = array_merge(
            [
                'name' => $item->name,
                'type' => $item->type,
                'user' => $item->user,
                'username' => $item->username,
            ],
            $this->editableColumns($item)
        );

        $assignments = implode(
            ', ',
            array_map(static fn(string $column): string => sprintf('`%s` = ?', $column), array_keys($columns))
        );

        $this->connection->query(
            sprintf('UPDATE `%s` SET %s WHERE `id` = ?', $this->tableName(), $assignments),
            [...array_values($columns), $item->getId()]
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
     * Stores the time the list last changed
     */
    public function setLastUpdate(playlist_object $item, int $time): void
    {
        $this->connection->query(
            sprintf('UPDATE `%s` SET `last_update` = ? WHERE `id` = ?', $this->tableName()),
            [$time, $item->getId()]
        );
    }

    /**
     * Replaces the set of users allowed to collaborate on the list
     *
     * @param int[] $userIds
     */
    public function updateCollaborators(playlist_object $item, array $userIds): void
    {
        $mapKey = $this->collaborateKey($item);

        // force int: $userIds lands unquoted in NOT IN () below
        $collaborate = implode(',', array_map('intval', $userIds));

        // the column and the map are the same fact stored twice, so they are written together
        $this->connection->query(
            sprintf('UPDATE `%s` SET `collaborate` = ? WHERE `id` = ?', $this->tableName()),
            [$collaborate, $item->getId()]
        );

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
     * Columns `persist()` should write beyond the four both tables share
     *
     * @return array<string, mixed>
     */
    protected function editableColumns(playlist_object $item): array
    {
        return [];
    }

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
