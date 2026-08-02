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
use Generator;
use PDO;
use Psr\Log\LoggerInterface;

final readonly class CatalogFilterRepository implements CatalogFilterRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    public function addCatalogToGroups(int $catalogId): void
    {
        // the DEFAULT group is id 0, so this cannot use the fetchColumn() loop the other id reads use
        $result = $this->connection->query('SELECT `id` FROM `catalog_filter_group` ORDER BY `id`');

        $groupIds = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $groupIds[] = (int) $row['id'];
        }

        foreach ($groupIds as $groupId) {
            $this->connection->query(
                'INSERT IGNORE INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) VALUES (?, ?, ?);',
                [$groupId, $catalogId, ($groupId === 0) ? 1 : 0]
            );
        }
    }

    public function addMissingCatalogsToDefaultGroup(): void
    {
        $this->connection->query(
            'INSERT IGNORE INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) SELECT 0, `catalog`.`id`, `catalog`.`enabled` FROM `catalog` WHERE `catalog`.`id` NOT IN (SELECT `catalog_id` AS `id` FROM `catalog_filter_group_map` WHERE `group_id` = 0);'
        );
    }

    public function collectGarbage(): void
    {
        $statements = [
            'DELETE FROM `catalog_filter_group_map` WHERE `group_id` NOT IN (SELECT `id` FROM `catalog_filter_group`);',
            'DELETE FROM `catalog_filter_group_map` WHERE `catalog_id` NOT IN (SELECT `id` FROM `catalog`);',
            "UPDATE IGNORE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT' AND `id` > 0;",
        ];

        // one statement that cannot run must not take the rest of the sweep down with it
        foreach ($statements as $statement) {
            try {
                $this->connection->query($statement);
            } catch (DatabaseException) {
                $this->logger->debug(
                    'collectGarbage error: ' . $statement,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    public function countCatalogs(int $groupId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(1) AS `count` FROM `catalog_filter_group_map` WHERE `group_id` = ? AND `enabled` = 1',
            [$groupId]
        );
    }

    public function createGroup(string $name): int
    {
        $this->connection->query('INSERT INTO `catalog_filter_group` (`name`) VALUES (?)', [$name]);

        return $this->connection->getLastInsertedId();
    }

    public function deleteGroup(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        try {
            $this->connection->query('DELETE FROM `catalog_filter_group` WHERE `id` = ?', [$groupId]);
            $this->connection->query('DELETE FROM `catalog_filter_group_map` WHERE `group_id` = ?', [$groupId]);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    public function findGroups(): Generator
    {
        $result = $this->connection->query('SELECT `id`, `name` FROM `catalog_filter_group` ORDER BY `name` ');

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            yield [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }
    }

    public function groupNameExists(string $name, int $excludeId = 0): bool
    {
        $params = [$name];
        $sql    = 'SELECT `id` FROM `catalog_filter_group` WHERE `name` = ?';
        if ($excludeId >= 0) {
            $sql .= ' AND `id` != ?';
            $params[] = $excludeId;
        }

        return $this->connection->fetchOne($sql, $params) !== false;
    }

    public function hasAccess(int $catalogId, int $userId): bool
    {
        // the system and guest users have no row in `user`, so they are held to the DEFAULT group
        [$sql, $params] = ($userId === -1)
            ? ['SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_id` = ? AND `enabled` = 1 AND `group_id` = 0;', [$catalogId]]
            : ['SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_id` = ? AND `enabled` = 1 AND `group_id` IN (SELECT `catalog_filter_group` FROM `user` WHERE `id` = ?);', [$catalogId, $userId]];

        return $this->connection->fetchOne($sql, $params) !== false;
    }

    public function insertCatalogsForGroup(int $groupId, array $enabledByCatalogId): bool
    {
        if ($enabledByCatalogId === []) {
            return true;
        }

        $params = [];
        $rows   = [];
        foreach ($enabledByCatalogId as $catalogId => $enabled) {
            $rows[]   = '(?, ?, ?)';
            $params[] = $groupId;
            $params[] = (int) $catalogId;
            $params[] = $enabled;
        }

        try {
            $this->connection->query(
                'INSERT INTO `catalog_filter_group_map` (`group_id`, `catalog_id`, `enabled`) VALUES ' . implode(',', $rows),
                $params
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    public function isCatalogEnabled(int $groupId, int $catalogId): bool
    {
        return $this->connection->fetchOne(
            'SELECT `enabled` FROM `catalog_filter_group_map` WHERE `group_id` = ? AND `catalog_id` = ? AND `enabled` = 1;',
            [$groupId, $catalogId]
        ) !== false;
    }

    public function renameGroup(int $groupId, string $name): void
    {
        $this->connection->query(
            'UPDATE `catalog_filter_group` SET `name` = ? WHERE `id` = ?;',
            [$name, $groupId]
        );
    }

    public function repairDefaultGroup(): bool
    {
        $row = $this->connection->fetchRow("SELECT `id`, `name` FROM `catalog_filter_group` WHERE `name` = 'DEFAULT';");
        if (is_array($row) && array_key_exists('id', $row) && ($row['id'] ?? '') == 0) {
            return false;
        }

        $this->connection->query("INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT');");
        $this->connection->query("UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT';");

        $increment = (int) $this->connection->fetchOne('SELECT MAX(`id`) AS `filter_count` FROM `catalog_filter_group`;') + 1;
        $this->connection->query(sprintf('ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = %d;', $increment));

        return true;
    }

    public function setCatalogEnabled(int $groupId, int $catalogId, int $enabled): bool
    {
        $exists = $this->connection->fetchOne(
            'SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `group_id` = ? AND `catalog_id` = ?',
            [$groupId, $catalogId]
        ) !== false;

        $sql = ($exists)
            ? 'UPDATE `catalog_filter_group_map` SET `enabled` = ? WHERE `group_id` = ? AND `catalog_id` = ?'
            : 'INSERT INTO `catalog_filter_group_map` SET `enabled` = ?, `group_id` = ?, `catalog_id` = ?';

        try {
            $this->connection->query($sql, [$enabled, $groupId, $catalogId]);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }
}
