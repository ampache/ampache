<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Repository\Model\ModelInterface;
use PDO;

/**
 * @template TModel as ModelInterface
 */
abstract class BaseRepository
{
    protected DatabaseConnectionInterface $connection;

    /**
     * @return class-string<TModel>
     */
    abstract protected function getModelClass(): string;

    abstract protected function getTableName(): string;

    /**
     * @return list<mixed>
     */
    abstract protected function getPrototypeParameters(): array;

    /**
     * Retrieve a single item by its id
     *
     * @return null|TModel
     */
    public function findById(int $objectId): ?object
    {
        $result = $this->connection->query(
            sprintf(
                'SELECT * FROM `%s` WHERE `id` = ?',
                $this->getTableName()
            ),
            [$objectId]
        );
        $result->setFetchMode(PDO::FETCH_CLASS, $this->getModelClass(), $this->getPrototypeParameters());

        $shout = $result->fetch();
        if ($shout === false) {
            return null;
        }

        return $shout;
    }

    /**
     * This function deletes the items entry
     *
     * @param TModel $record
     */
    public function delete(object $record): void
    {
        $this->connection->query(
            sprintf(
                'DELETE FROM `%s` WHERE `id` = ?',
                $this->getTableName()
            ),
            [$record->getId()]
        );
    }

    /**
     * Returns a new item
     *
     * @return TModel
     */
    public function prototype(): ModelInterface
    {
        $className = $this->getModelClass();

        return new $className(...$this->getPrototypeParameters());
    }
}
