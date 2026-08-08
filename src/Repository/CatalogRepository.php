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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Catalog\CatalogSubTypeFieldEnum;
use Ampache\Module\Catalog\CatalogTypeEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\Database\Exception\InsertIdInvalidException;
use Ampache\Repository\Model\CatalogFieldEnum;
use PDO;

final readonly class CatalogRepository implements CatalogRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private ConfigContainerInterface $configContainer,
    ) {}

    /**
     * Creates a backend's own settings table, wrapping its columns in the id and catalog_id every one has
     *
     * @param array<string, string> $columns column => its SQL type, e.g. `VARCHAR(255)`; a string type
     *                                       is given the database collation
     */
    public function createSubTypeTable(CatalogTypeEnum $type, array $columns): void
    {
        $collation = (string) $this->configContainer->get('database_collation') ?: 'utf8mb4_unicode_ci';
        $charset   = (string) $this->configContainer->get('database_charset') ?: 'utf8mb4';
        $engine    = (string) $this->configContainer->get('database_engine') ?: 'InnoDB';

        $definitions = ['`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY'];
        foreach ($columns as $column => $columnType) {
            $collate = (str_contains(strtoupper($columnType), 'CHAR') || str_contains(strtoupper($columnType), 'TEXT'))
                ? ' COLLATE ' . $collation
                : '';
            $definitions[] = sprintf('`%s` %s%s NOT NULL', CatalogSubTypeFieldEnum::from($column)->value, $columnType, $collate);
        }

        $definitions[] = '`catalog_id` INT(11) NOT NULL';

        $this->connection->query(
            sprintf(
                'CREATE TABLE `%s` (%s) ENGINE = %s DEFAULT CHARSET=%s COLLATE=%s',
                $type->tableName(),
                implode(', ', $definitions),
                $engine,
                $charset,
                $collation
            )
        );
    }

    public function deleteByType(CatalogTypeEnum $type): void
    {
        $this->connection->query('DELETE FROM `catalog` WHERE `catalog_type` = ?', [$type->value]);
    }

    public function deleteRow(int $catalogId): bool
    {
        try {
            $this->connection->query('DELETE FROM `catalog` WHERE `id` = ?', [$catalogId]);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    public function deleteSubTypeRow(CatalogTypeEnum $type, int $catalogId): bool
    {
        try {
            $this->connection->query(
                sprintf('DELETE FROM `%s` WHERE `catalog_id` = ?', $type->tableName()),
                [$catalogId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Uninstalling a backend twice is allowed to find its table already gone
     */
    public function dropSubTypeTable(CatalogTypeEnum $type): void
    {
        try {
            $this->connection->query(sprintf('DROP TABLE `%s`', $type->tableName()));
        } catch (DatabaseException) {
            // the table is what we wanted removed, so its absence is the outcome we were after
        }
    }

    /**
     * The catalog whose configured path this file sits under, or null when no catalog claims it
     */
    public function findCatalogIdByPathPrefix(CatalogTypeEnum $type, string $filePath): ?int
    {
        $catalogId = $this->connection->fetchOne(
            sprintf("SELECT `catalog_id` FROM `%s` WHERE ? LIKE CONCAT(`path`, '%%')", $type->tableName()),
            [$filePath]
        );

        return ($catalogId === false || $catalogId === null)
            ? null
            : (int) $catalogId;
    }

    public function findEnabled(int $catalogId): ?bool
    {
        $enabled = $this->connection->fetchOne('SELECT `enabled` FROM `catalog` WHERE `id` = ?', [$catalogId]);

        return ($enabled === false || $enabled === null)
            ? null
            : (bool) $enabled;
    }

    public function findName(int $catalogId): string
    {
        $name = $this->connection->fetchOne('SELECT `name` FROM `catalog` WHERE `id` = ?', [$catalogId]);

        return ($name === false || $name === null)
            ? ''
            : (string) $name;
    }

    public function findSubTypeId(CatalogTypeEnum $type, int $catalogId): ?int
    {
        // a backend that has been uninstalled leaves catalog rows behind with no settings table to read
        try {
            $rowId = $this->connection->fetchOne(
                sprintf('SELECT `id` FROM `%s` WHERE `catalog_id` = ?', $type->tableName()),
                [$catalogId]
            );
        } catch (DatabaseException) {
            return null;
        }

        return ($rowId === false || $rowId === null)
            ? null
            : (int) $rowId;
    }

    public function findType(int $catalogId): ?string
    {
        $type = $this->connection->fetchOne('SELECT `catalog_type` FROM `catalog` WHERE `id` = ?', [$catalogId]);

        return ($type === false || $type === null)
            ? null
            : (string) $type;
    }

    public function getIds(
        ?string $gatherType = null,
        bool $enabledOnly = false,
        ?int $filterUserId = null,
    ): array {
        $params = [];
        $where  = [];
        if ($gatherType !== null && $gatherType !== '') {
            $where[]  = '`gather_types` = ?';
            $params[] = $gatherType;
        }

        if ($enabledOnly) {
            $where[] = '`enabled` = 1';
        }

        // the system and guest users have no row in `user`, so they are held to the DEFAULT filter group
        if ($filterUserId === -1) {
            $where[] = '`catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled` = 1)';
        } elseif ($filterUserId !== null && $filterUserId > 0) {
            $where[]  = '`catalog`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled` = 1)';
            $params[] = $filterUserId;
        }

        $sql = 'SELECT `id` FROM `catalog` ';
        if ($where !== []) {
            $sql .= 'WHERE ' . implode(' AND ', $where) . ' ';
        }

        $result = $this->connection->query($sql . 'ORDER BY `name`;', $params);

        $catalogIds = [];
        while ($catalogId = $result->fetchColumn()) {
            $catalogIds[] = (int) $catalogId;
        }

        return $catalogIds;
    }

    /**
     * @param array<int> $catalogIds
     * @return array<int, string>
     */
    public function getNamesByIds(array $catalogIds): array
    {
        if ($catalogIds === []) {
            return [];
        }

        // the ids are bound positionally, so they have to be a list whatever the caller passed
        $ids          = array_values($catalogIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $result       = $this->connection->query(
            sprintf('SELECT `id`, `name` FROM `catalog` WHERE `id` IN (%s) ORDER BY `name`;', $placeholders),
            $ids
        );

        $names = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $names[(int) $row['id']] = (string) $row['name'];
        }

        return $names;
    }

    /**
     * The configured path of every catalog of one backend, keyed by catalog id
     *
     * @return array<int, string>
     */
    public function getSubTypePaths(CatalogTypeEnum $type): array
    {
        $result = $this->connection->query(sprintf('SELECT `catalog_id`, `path` FROM `%s`', $type->tableName()));

        $paths = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $paths[(int) $row['catalog_id']] = (string) $row['path'];
        }

        return $paths;
    }

    public function insert(
        string $name,
        string $type,
        string $renamePattern,
        string $sortPattern,
        string $gatherTypes,
    ): int {
        try {
            $this->connection->query(
                'INSERT INTO `catalog` (`name`, `catalog_type`, `rename_pattern`, `sort_pattern`, `gather_types`) VALUES (?, ?, ?, ?, ?)',
                [$name, $type, $renamePattern, $sortPattern, $gatherTypes]
            );

            return $this->connection->getLastInsertedId();
        } catch (DatabaseException|InsertIdInvalidException) {
            // the caller reports the failure to the admin, so a missing id is a 0 rather than a throw
            return 0;
        }
    }

    /**
     * Adds a catalog's row to its backend's settings table
     *
     * @param array<string, mixed> $values column => value, without `catalog_id`
     */
    public function insertSubType(CatalogTypeEnum $type, array $values, int $catalogId): bool
    {
        $columns = array_map(
            static fn(string $column): string => '`' . CatalogSubTypeFieldEnum::from($column)->value . '`',
            array_keys($values)
        );
        $columns[] = '`catalog_id`';

        try {
            $this->connection->query(
                sprintf(
                    'INSERT INTO `%s` (%s) VALUES (%s)',
                    $type->tableName(),
                    implode(', ', $columns),
                    implode(', ', array_fill(0, count($columns), '?'))
                ),
                [...array_values($values), $catalogId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    public function setField(int $catalogId, CatalogFieldEnum $field, int|string $value): bool
    {
        try {
            $this->connection->query(
                sprintf('UPDATE `catalog` SET `%s` = ? WHERE `id` = ?', $field->value),
                [$value, $catalogId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Whether a backend's settings table has been created yet
     */
    public function subTypeTableExists(CatalogTypeEnum $type): bool
    {
        return $this->connection->fetchOne(
            sprintf("SHOW TABLES LIKE '%s'", $type->tableName())
        ) !== false;
    }

    /**
     * Whether a backend already has a catalog holding this value, which is what stops a duplicate
     */
    public function subTypeValueExists(CatalogTypeEnum $type, string $column, string $value): bool
    {
        return $this->connection->fetchOne(
            sprintf('SELECT `id` FROM `%s` WHERE `%s` = ?', $type->tableName(), CatalogSubTypeFieldEnum::from($column)->value),
            [$value]
        ) !== false;
    }

    public function updateSettings(int $catalogId, string $name, string $renamePattern, string $sortPattern): void
    {
        $this->connection->query(
            'UPDATE `catalog` SET `name` = ?, `rename_pattern` = ?, `sort_pattern` = ? WHERE `id` = ?',
            [$name, $renamePattern, $sortPattern, $catalogId]
        );
    }

    /**
     * Points a catalog at another path on disk
     */
    public function updateSubTypePath(CatalogTypeEnum $type, int $catalogId, string $path): void
    {
        $this->connection->query(
            sprintf('UPDATE `%s` SET `path` = ? WHERE `catalog_id` = ?', $type->tableName()),
            [$path, $catalogId]
        );
    }
}
