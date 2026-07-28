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
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\User;
use PDO;

final readonly class CollectionRepository implements CollectionRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection) {}

    public function addItem(int $collectionId, int $objectId, string $objectType): void
    {
        // INSERT IGNORE against the unique key makes adding the same object twice a no-op
        $this->connection->query(
            'INSERT IGNORE INTO `collection_map` (`collection`, `object_id`, `object_type`, `track`) VALUES (?, ?, ?, (SELECT COALESCE(MAX(`track`), 0) + 1 FROM `collection_map` AS `existing` WHERE `existing`.`collection` = ?));',
            [$collectionId, $objectId, $objectType, $collectionId]
        );

        $this->touch($collectionId);
    }

    public function collectGarbage(): void
    {
        try {
            // A member whose object was deleted is dropped; the collection itself survives
            foreach (Collection::VALID_TYPES as $objectType) {
                $table = ($objectType === 'genre') ? 'tag' : $objectType;
                $this->connection->query(
                    sprintf(
                        'DELETE FROM `collection_map` WHERE `object_type` = ? AND `object_id` NOT IN (SELECT `id` FROM `%s`);',
                        $table
                    ),
                    [$objectType]
                );
            }

            $this->connection->query('DELETE FROM `collection` WHERE `user` IS NOT NULL AND `user` NOT IN (SELECT `id` FROM `user`);');
            $this->connection->query('DELETE FROM `collection_map` WHERE `collection` NOT IN (SELECT `id` FROM `collection`);');
        } catch (DatabaseException) {
            debug_event(self::class, 'collectGarbage error', 5);
        }
    }

    public function countByUser(?User $user): int
    {
        // Same visibility scope as getByUser(), so the link appears exactly when a browse would list something
        $userId = ($user?->getId()) ?? -1;

        return (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM `collection` WHERE (`user` = ? OR `type` = 'public' OR FIND_IN_SET(?, `collaborate`) > 0);",
            [$userId, $userId]
        );
    }

    public function create(
        string $name,
        User $user,
        string $type = 'private',
        ?string $objectType = null,
    ): ?int {
        $this->connection->query(
            'INSERT INTO `collection` (`name`, `user`, `username`, `type`, `object_type`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?, ?, ?);',
            [$name, $user->getId(), $user->username, $type, $objectType, time(), time()]
        );

        $collectionId = (int) $this->connection->getLastInsertedId();

        return ($collectionId > 0) ? $collectionId : null;
    }

    public function delete(int $collectionId): void
    {
        $this->connection->query('DELETE FROM `collection_map` WHERE `collection` = ?;', [$collectionId]);
        $this->connection->query('DELETE FROM `collection` WHERE `id` = ?;', [$collectionId]);

        $this->invalidate($collectionId);
    }

    public function findById(int $collectionId): ?Collection
    {
        $collection = new Collection($collectionId);

        return ($collection->isNew())
            ? null
            : $collection;
    }

    /**
     * @return list<int>
     */
    public function getByUser(User $user, ?string $objectType = null): array
    {
        // Collaborations count as yours to list, matching how `PlaylistQuery` scopes a browse for a user.
        $params = [$user->getId(), $user->getId()];
        $sql    = "SELECT `id` FROM `collection` WHERE (`user` = ? OR `type` = 'public' OR FIND_IN_SET(?, `collaborate`) > 0)";

        if ($objectType !== null) {
            $sql .= ' AND `object_type` = ?';
            $params[] = $objectType;
        }

        $sql .= ' ORDER BY `name`;';

        $result = $this->connection->query($sql, $params);

        $ids = [];
        while ($rowId = $result->fetchColumn()) {
            $ids[] = (int) $rowId;
        }

        return $ids;
    }

    public function getItemCount(int $collectionId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM `collection_map` WHERE `collection` = ?;',
            [$collectionId]
        );
    }

    /**
     * @return list<array{'id': int, 'object_id': int, 'object_type': string, 'track': int}>
     */
    public function getItems(int $collectionId): array
    {
        $result = $this->connection->query(
            'SELECT `id`, `object_id`, `object_type`, `track` FROM `collection_map` WHERE `collection` = ? ORDER BY `track`, `id`;',
            [$collectionId]
        );

        $items = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $items[] = [
                'id' => (int) $row['id'],
                'object_id' => (int) $row['object_id'],
                'object_type' => (string) $row['object_type'],
                'track' => (int) $row['track'],
            ];
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public function getItemTypes(int $collectionId): array
    {
        $result = $this->connection->query(
            'SELECT DISTINCT `object_type` FROM `collection_map` WHERE `collection` = ?;',
            [$collectionId]
        );

        $types = [];
        while ($type = $result->fetchColumn()) {
            $types[] = (string) $type;
        }

        return $types;
    }

    public function objectExists(string $objectType, int $objectId): bool
    {
        // Asked of the type's own table: several models set their id from the constructor, so `isNew()` lies
        if (!Collection::isValidType($objectType)) {
            return false;
        }

        return (bool) $this->connection->fetchOne(
            sprintf('SELECT `id` FROM `%s` WHERE `id` = ?;', Collection::normalizeType($objectType)),
            [$objectId]
        );
    }

    public function removeItem(int $collectionId, int $objectId, string $objectType): void
    {
        $this->connection->query(
            'DELETE FROM `collection_map` WHERE `collection` = ? AND `object_id` = ? AND `object_type` = ?;',
            [$collectionId, $objectId, $objectType]
        );

        $this->touch($collectionId);
    }

    public function update(
        int $collectionId,
        ?string $name = null,
        ?string $type = null,
        ?string $objectType = null,
        ?string $collaborate = null,
    ): void {
        $sets   = [];
        $params = [];

        // Only the fields actually supplied are touched, so an edit of one field cannot blank the others.
        foreach (['name' => $name, 'type' => $type, 'collaborate' => $collaborate] as $column => $value) {
            if ($value !== null) {
                $sets[]   = sprintf('`%s` = ?', $column);
                $params[] = $value;
            }
        }

        // An empty string is how a caller un-pins a collection back to mixed, so it is distinct from null here.
        if ($objectType !== null) {
            $sets[]   = '`object_type` = ?';
            $params[] = ($objectType === '') ? null : $objectType;
        }

        if ($sets === []) {
            return;
        }

        $sets[]   = '`last_update` = ?';
        $params[] = time();
        $params[] = $collectionId;

        $this->connection->query(
            sprintf('UPDATE `collection` SET %s WHERE `id` = ?;', implode(', ', $sets)),
            $params
        );

        $this->invalidate($collectionId);
    }

    /**
     * Drop the request-scoped row cache for one collection.
     *
     * `get_info()` would otherwise serve the pre-write row for the rest of the request.
     */
    private function invalidate(int $collectionId): void
    {
        Collection::remove_from_cache('collection', $collectionId);
    }

    private function touch(int $collectionId): void
    {
        $this->connection->query(
            'UPDATE `collection` SET `last_update` = ?, `last_count` = (SELECT COUNT(*) FROM `collection_map` WHERE `collection` = ?) WHERE `id` = ?;',
            [time(), $collectionId, $collectionId]
        );

        $this->invalidate($collectionId);
    }
}
