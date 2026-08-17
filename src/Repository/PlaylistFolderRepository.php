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
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\PlaylistFolder;
use Ampache\Repository\Model\User;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Reads and writes the per-user playlist folder tree and the placements filed into it
 *
 * Depth is walked one segment at a time rather than stored as a materialised path, so re-parenting a subtree
 * is a single-row update and no path can go stale.
 */
final readonly class PlaylistFolderRepository implements PlaylistFolderRepositoryInterface
{
    /**
     * How deep a name path may go before it is treated as malformed rather than merely missing.
     */
    private const int MAX_PATH_DEPTH = 32;

    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    public function collectGarbage(): void
    {
        $statements = [];

        // A placement whose list was deleted goes; the folder holding it survives
        foreach (PlaylistFolder::VALID_TYPES as $objectType) {
            $statements[] = [
                sprintf(
                    'DELETE FROM `playlist_folder_map` WHERE `object_type` = ? AND `object_id` NOT IN (SELECT `id` FROM `%s`);',
                    $objectType
                ),
                [$objectType],
            ];
        }

        $statements[] = ['DELETE FROM `playlist_folder` WHERE `user` NOT IN (SELECT `id` FROM `user`);', []];
        $statements[] = ['DELETE FROM `playlist_folder_map` WHERE `user` NOT IN (SELECT `id` FROM `user`);', []];

        // A dangling parent or folder is re-homed to the root, so a bug elsewhere flattens a tree instead of losing it
        $statements[] = [
            sprintf(
                'UPDATE `playlist_folder` SET `parent` = %d WHERE `parent` != %d AND `parent` NOT IN (SELECT `id` FROM (SELECT `id` FROM `playlist_folder`) AS `existing`);',
                PlaylistFolder::ROOT,
                PlaylistFolder::ROOT
            ),
            [],
        ];
        $statements[] = [
            sprintf(
                'UPDATE `playlist_folder_map` SET `folder` = %d WHERE `folder` != %d AND `folder` NOT IN (SELECT `id` FROM `playlist_folder`);',
                PlaylistFolder::ROOT,
                PlaylistFolder::ROOT
            ),
            [],
        ];

        // one sweep that cannot run must not take the rest down with it
        foreach ($statements as $statement) {
            try {
                $this->connection->query($statement[0], $statement[1]);
            } catch (DatabaseException) {
                $this->logger->debug(
                    'collectGarbage error: ' . $statement[0],
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    public function create(
        User $user,
        string $name,
        int $parentId = PlaylistFolder::ROOT,
        ?int $sortOrder = null,
    ): ?int {
        if (!PlaylistFolder::isValidName($name)) {
            return null;
        }

        if (!$this->isOwnParent($user, $parentId)) {
            return null;
        }

        $name = trim($name);

        return $this->insert(
            $user->getId(),
            $name,
            $parentId,
            $sortOrder ?? $this->nextSortOrder($user->getId(), $parentId)
        );
    }

    public function delete(int $folderId): bool
    {
        if (!$this->isEmpty($folderId)) {
            return false;
        }

        $this->connection->query('DELETE FROM `playlist_folder` WHERE `id` = ?;', [$folderId]);
        $this->invalidate($folderId);

        return true;
    }

    public function findById(int $folderId): ?PlaylistFolder
    {
        if ($folderId <= PlaylistFolder::ROOT) {
            return null;
        }

        $row = $this->connection->query(
            'SELECT * FROM `playlist_folder` WHERE `id` = ?;',
            [$folderId]
        )->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? PlaylistFolder::fromRow($row)
            : null;
    }

    public function findByPath(User $user, string $path): ?PlaylistFolder
    {
        $segments = array_values(
            array_filter(
                explode(PlaylistFolder::PATH_SEPARATOR, $path),
                static fn(string $segment): bool => $segment !== ''
            )
        );
        if ($segments === [] || count($segments) > self::MAX_PATH_DEPTH) {
            return null;
        }

        // Each step is an indexed lookup on `unique_playlist_folder`, so depth costs one small query per segment
        $folderId = PlaylistFolder::ROOT;
        foreach ($segments as $segment) {
            $folderId = (int) $this->connection->fetchOne(
                'SELECT `id` FROM `playlist_folder` WHERE `user` = ? AND `parent` = ? AND `name` = ?;',
                [$user->getId(), $folderId, $segment]
            );

            if ($folderId === 0) {
                return null;
            }
        }

        return $this->findById($folderId);
    }

    /**
     * @return list<PlaylistFolder>
     */
    public function getChildren(User $user, int $parentId = PlaylistFolder::ROOT): array
    {
        return $this->hydrate(
            'SELECT * FROM `playlist_folder` WHERE `user` = ? AND `parent` = ? ORDER BY `sort_order`, `name`;',
            [$user->getId(), $parentId]
        );
    }

    /**
     * @return array<int, int>
     */
    public function getItemCounts(User $user): array
    {
        $result = $this->connection->query(
            'SELECT `folder`, COUNT(*) AS `items` FROM `playlist_folder_map` WHERE `user` = ? GROUP BY `folder`;',
            [$user->getId()]
        );

        $counts = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $counts[(int) $row['folder']] = (int) $row['items'];
        }

        return $counts;
    }

    /**
     * @return list<int>
     */
    public function getPlacedObjectIds(User $user, string $objectType): array
    {
        $objectType = PlaylistFolder::normalizeType($objectType);

        $result = $this->connection->query(
            'SELECT `object_id` FROM `playlist_folder_map` WHERE `user` = ? AND `object_type` = ? AND `folder` != ?;',
            [$user->getId(), $objectType, PlaylistFolder::ROOT]
        );

        $ids = [];
        while ($objectId = $result->fetchColumn()) {
            $ids[] = (int) $objectId;
        }

        return $ids;
    }

    /**
     * @return array{folder: int, sort_order: int}|null
     */
    public function getPlacement(User $user, int $objectId, string $objectType): ?array
    {
        $objectType = PlaylistFolder::normalizeType($objectType);

        $row = $this->connection->query(
            'SELECT `folder`, `sort_order` FROM `playlist_folder_map` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ?;',
            [$user->getId(), $objectType, $objectId]
        )->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            return null;
        }

        return [
            'folder' => (int) $row['folder'],
            'sort_order' => (int) $row['sort_order'],
        ];
    }

    /**
     * @return array<string, array{folder: int, sort_order: int}>
     */
    public function getPlacementMap(User $user): array
    {
        $result = $this->connection->query(
            'SELECT `folder`, `object_id`, `object_type`, `sort_order` FROM `playlist_folder_map` WHERE `user` = ?;',
            [$user->getId()]
        );

        $placements = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $key = sprintf('%s-%d', (string) $row['object_type'], (int) $row['object_id']);

            $placements[$key] = [
                'folder' => (int) $row['folder'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }

        return $placements;
    }

    /**
     * @return list<array{object_id: int, object_type: string, sort_order: int}>
     */
    public function getPlacements(User $user, int $folderId): array
    {
        $result = $this->connection->query(
            'SELECT `object_id`, `object_type`, `sort_order` FROM `playlist_folder_map` WHERE `user` = ? AND `folder` = ? ORDER BY `sort_order`, `id`;',
            [$user->getId(), $folderId]
        );

        $placements = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $placements[] = [
                'object_id' => (int) $row['object_id'],
                'object_type' => (string) $row['object_type'],
                'sort_order' => (int) $row['sort_order'],
            ];
        }

        return $placements;
    }

    /**
     * @return list<PlaylistFolder>
     */
    public function getTree(User $user): array
    {
        return $this->hydrate(
            'SELECT * FROM `playlist_folder` WHERE `user` = ? ORDER BY `parent`, `sort_order`, `name`;',
            [$user->getId()]
        );
    }

    public function isEmpty(int $folderId): bool
    {
        if ($folderId <= PlaylistFolder::ROOT) {
            return false;
        }

        $children = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM `playlist_folder` WHERE `parent` = ?;',
            [$folderId]
        );

        if ($children > 0) {
            return false;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM `playlist_folder_map` WHERE `folder` = ?;',
            [$folderId]
        ) === 0;
    }

    public function persist(PlaylistFolder $folder): ?int
    {
        if (!PlaylistFolder::isValidName($folder->getName()) || $folder->getUserId() <= 0) {
            return null;
        }

        return $this->insert(
            $folder->getUserId(),
            $folder->getName(),
            $folder->getParentId(),
            $folder->getSortOrder()
        );
    }

    public function place(
        User $user,
        int $objectId,
        string $objectType,
        ?int $folderId,
        ?int $sortOrder = null,
    ): bool {
        $objectType = PlaylistFolder::normalizeType($objectType);
        if (!PlaylistFolder::isValidType($objectType) || $objectId <= 0) {
            return false;
        }

        $folderId = $folderId ?? PlaylistFolder::ROOT;
        if (!$this->isOwnParent($user, $folderId)) {
            return false;
        }

        // Root with no stated position is the absence of a row, which is what makes an unfiled list free
        if ($folderId === PlaylistFolder::ROOT && $sortOrder === null) {
            $this->unplace($user, $objectId, $objectType);

            return true;
        }

        $sortOrder = $sortOrder ?? $this->nextSortOrder($user->getId(), $folderId);

        // The unique key turns a second filing into a move, so a list is never in two folders at once
        $this->connection->query(
            'INSERT INTO `playlist_folder_map` (`user`, `folder`, `object_id`, `object_type`, `sort_order`) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `folder` = VALUES(`folder`), `sort_order` = VALUES(`sort_order`);',
            [$user->getId(), $folderId, $objectId, $objectType, $sortOrder]
        );

        return true;
    }

    public function unplace(User $user, int $objectId, string $objectType): void
    {
        $this->connection->query(
            'DELETE FROM `playlist_folder_map` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ?;',
            [$user->getId(), PlaylistFolder::normalizeType($objectType), $objectId]
        );
    }

    public function update(
        int $folderId,
        ?string $name = null,
        ?int $parentId = null,
        ?int $sortOrder = null,
    ): bool {
        // The owner is read directly rather than through the model, so a write never warms the row cache
        $userId = (int) $this->connection->fetchOne(
            'SELECT `user` FROM `playlist_folder` WHERE `id` = ?;',
            [$folderId]
        );

        if ($userId === 0) {
            return false;
        }

        if ($name !== null && !PlaylistFolder::isValidName($name)) {
            return false;
        }

        if ($parentId !== null && $this->wouldCycle($folderId, $parentId)) {
            return false;
        }

        if ($parentId !== null && $parentId !== PlaylistFolder::ROOT && !$this->isOwnFolder($userId, $parentId)) {
            return false;
        }

        $sets   = [];
        $params = [];

        // Only the fields actually supplied are touched, so a rename cannot re-home the folder
        if ($name !== null) {
            $sets[]   = '`name` = ?';
            $params[] = trim($name);
        }

        if ($parentId !== null) {
            $sets[]   = '`parent` = ?';
            $params[] = $parentId;
        }

        if ($sortOrder !== null) {
            $sets[]   = '`sort_order` = ?';
            $params[] = $sortOrder;
        }

        if ($sets === []) {
            return false;
        }

        $sets[]   = '`last_update` = ?';
        $params[] = time();
        $params[] = $folderId;

        try {
            $this->connection->query(
                sprintf('UPDATE `playlist_folder` SET %s WHERE `id` = ?;', implode(', ', $sets)),
                $params
            );
        } catch (DatabaseException) {
            // `unique_playlist_folder` rejected a name a sibling already holds, case-insensitively
            return false;
        }

        $this->invalidate($folderId);

        return true;
    }

    public function wouldCycle(int $folderId, int $newParentId): bool
    {
        if ($folderId <= PlaylistFolder::ROOT) {
            return false;
        }

        if ($folderId === $newParentId) {
            return true;
        }

        // Walk up from the proposed parent; meeting the folder itself means the move would detach the subtree
        $seen     = [];
        $ancestor = $newParentId;
        while ($ancestor > PlaylistFolder::ROOT && !isset($seen[$ancestor])) {
            if ($ancestor === $folderId) {
                return true;
            }

            $seen[$ancestor] = true;
            $ancestor        = (int) $this->connection->fetchOne(
                'SELECT `parent` FROM `playlist_folder` WHERE `id` = ?;',
                [$ancestor]
            );
        }

        return false;
    }

    /**
     * @param list<int|string> $params
     * @return list<PlaylistFolder>
     */
    private function hydrate(string $sql, array $params): array
    {
        $result = $this->connection->query($sql, $params);

        $folders = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $folders[] = PlaylistFolder::fromRow($row);
        }

        return $folders;
    }

    /**
     * Store one folder, reporting null when a sibling already holds the name
     */
    private function insert(int $userId, string $name, int $parentId, int $sortOrder): ?int
    {
        try {
            $this->connection->query(
                'INSERT INTO `playlist_folder` (`user`, `parent`, `name`, `sort_order`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?, ?);',
                [$userId, $parentId, $name, $sortOrder, time(), time()]
            );
        } catch (DatabaseException) {
            return null;
        }

        $folderId = $this->connection->getLastInsertedId();

        return ($folderId > 0) ? $folderId : null;
    }

    /**
     * Drop the request-scoped row cache for one folder.
     *
     * `get_info()` would otherwise serve the pre-write row for the rest of the request.
     */
    private function invalidate(int $folderId): void
    {
        PlaylistFolder::remove_from_cache('playlist_folder', $folderId);
    }

    private function isOwnFolder(int $userId, int $folderId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT `id` FROM `playlist_folder` WHERE `id` = ? AND `user` = ?;',
            [$folderId, $userId]
        );
    }

    /**
     * Whether a user may hang something below this folder; the root always qualifies
     */
    private function isOwnParent(User $user, int $parentId): bool
    {
        return $parentId === PlaylistFolder::ROOT || $this->isOwnFolder($user->getId(), $parentId);
    }

    /**
     * The next free position among a parent's children
     *
     * Folders and placements share one ordering space, so both tables are consulted.
     */
    private function nextSortOrder(int $userId, int $parentId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT MAX(`sort_order`) FROM (SELECT `sort_order` FROM `playlist_folder` WHERE `user` = ? AND `parent` = ? UNION ALL SELECT `sort_order` FROM `playlist_folder_map` WHERE `user` = ? AND `folder` = ?) AS `siblings`;',
            [$userId, $parentId, $userId, $parentId]
        ) + 1;
    }
}
