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
use Ampache\Module\Database\Exception\InsertIdInvalidException;

final readonly class TmpBrowseRepository implements TmpBrowseRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection) {}

    public function collectGarbage(): void
    {
        $this->connection->query(
            'DELETE FROM `tmp_browse` USING `tmp_browse` LEFT JOIN `session` ON `session`.`id` = `tmp_browse`.`sid` WHERE `session`.`id` IS NULL'
        );
    }

    public function create(string $sessionId, string $data): ?int
    {
        try {
            $this->connection->query(
                'INSERT INTO `tmp_browse` (`sid`, `data`) VALUES(?, ?)',
                [$sessionId, $data]
            );

            return $this->connection->getLastInsertedId();
        } catch (DatabaseException|InsertIdInvalidException) {
            return null;
        }
    }

    /**
     * @return array{data?: ?string, object_data?: ?string}
     */
    public function getRow(int $browseId, string $sessionId): array
    {
        $row = $this->connection->fetchRow(
            'SELECT `data`, `object_data` FROM `tmp_browse` WHERE `id` = ? AND `sid` = ?',
            [$browseId, $sessionId]
        );

        return ($row === false)
            ? []
            : $row;
    }

    public function updateObjectData(int $browseId, string $sessionId, string $objectData): void
    {
        $this->connection->query(
            'UPDATE `tmp_browse` SET `object_data` = ? WHERE `sid` = ? AND `id` = ?',
            [$objectData, $sessionId, $browseId]
        );
    }

    public function updateState(int $browseId, string $sessionId, string $data): void
    {
        $this->connection->query(
            'UPDATE `tmp_browse` SET `data` = ? WHERE `sid` = ? AND `id` = ?',
            [$data, $sessionId, $browseId]
        );
    }
}
